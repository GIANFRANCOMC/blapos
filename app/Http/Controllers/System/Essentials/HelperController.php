<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Helpers\System\{ApiResponse, Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Mail\{SaleMail};
use App\Models\System\Sales\{SaleHeader};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Mail, Schema, Validator};
use stdClass;

class HelperController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Essentials.helper";

    private const DOCUMENT_LOOKUP_SERVICE = "apiperu";

    private const DOCUMENT_LOOKUP_ACTION = "document_lookup";

    public function searchDocumentNumber(Request $request): JsonResponse {

        $user = $this->getAuthUser();
        $company = app(TenantCompanyContext::class)->get();
        $companyId = (int) $company->id;

        $validator = Validator::make($request->all(), [
            "document_number" => ["required", "regex:/^\d{8,11}$/"],
            "type" => ["required", "in:dni,ruc"],
        ], [
            "document_number.required" => "Ingresa el número de documento.",
            "document_number.regex" => "El número de documento debe tener entre 8 y 11 dígitos.",
            "type.required" => "Selecciona el tipo de documento.",
            "type.in" => "El tipo de documento debe ser DNI o RUC.",
        ]);

        if($validator->fails()) {

            $this->logExternalApiRequest($request, $companyId, $user->id ?? null, "blocked");

            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                "Revisa los datos antes de consultar."
            );

        }

        if(empty($company->token_api_misc)) {

            $this->logExternalApiRequest($request, $companyId, $user->id ?? null, "blocked");

            return ApiResponse::success([
                "external_request_usage" => $this->getExternalApiMonthlyUsage($companyId),
            ], "Debe ingresar el Token API - Misc.");

        }

        [$success, $message, $data] = $this->requestDocumentLookup(
            (string) $request->type,
            (string) $request->document_number,
            (string) $company->token_api_misc
        );

        $this->logExternalApiRequest($request, $companyId, $user->id ?? null, $success ? "success" : "failed");

        $usage = $this->getExternalApiMonthlyUsage($companyId);
        $data["external_request_usage"] = $usage;

        if($usage["has_warning"]) {

            $message .= " Este mes registra {$usage["used"]} consultas externas; revisa el consumo mensual.";

        }

        return ApiResponse::success($data, $message);

    }

    public function sendEmail(Request $request): JsonResponse {

        try {

            $validator = Validator::make($request->all(), [
                "email" => ["required", "email:rfc", "max:255"],
                "message" => ["required", "string", "max:5000"],
                "serie_sequential" => ["nullable", "string", "max:100"],
                "id" => ["nullable", "integer", "min:1"],
            ], [
                "email.required" => "Ingresa el correo destino.",
                "email.email" => "Ingresa un correo válido.",
                "message.required" => "Ingresa el mensaje del correo.",
                "message.max" => "El mensaje no debe superar 5000 caracteres.",
            ]);

            if($validator->fails()) {

                return ApiResponse::validationError(
                    $validator->errors()->toArray(),
                    "Revisa los datos antes de enviar el correo."
                );

            }

            $sale = $request->filled("id")
                ? SaleHeader::query()
                    ->whereKey((int) $request->id)
                    ->with(["serie.branch", "holder"])
                    ->first()
                : null;

            $serieSequential = trim((string) ($request->serie_sequential ?: $sale?->serie_sequential));
            $branchName = $sale?->serie?->branch?->name;
            $customerName = $sale?->holder?->name;
            $company = app(TenantCompanyContext::class)->get();

            $mail = new stdClass();
            $mail->subject = trim(($serieSequential !== "" ? "Venta {$serieSequential}" : "Venta").($branchName ? " - {$branchName}" : ""))." - ".($company?->commercial_name ?: config("app.name"));
            $mail->message = (string) $request->message;
            $mail->customer_name = $customerName;
            $mail->branch_name = $branchName;
            $mail->company_name = $company?->commercial_name ?: config("app.name");
            $mail->owner_app = Utilities::getOwnerApp();

            Mail::to((string) $request->email)->send(new SaleMail($mail));

            return ApiResponse::success(null, "Correo enviado correctamente.");

        }catch(\Throwable $e) {

            return $this->handleException($e, "send_email");

        }

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

    private function requestDocumentLookup(string $type, string $documentNumber, string $token): array {

        $params = json_encode([$type => $documentNumber]);
        $curlApi = curl_init();

        curl_setopt_array($curlApi, [
            CURLOPT_URL => "https://apiperu.dev/api/{$type}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Bearer ".$token,
            ],
        ]);

        $responseApi = curl_exec($curlApi);
        $errorApi = curl_error($curlApi);
        curl_close($curlApi);

        if($errorApi) {

            return [false, $errorApi, []];

        }

        $dataApi = json_decode((string) $responseApi);

        $success = (bool) ($dataApi->success ?? false);

        if(!$success) {

            return [false, $dataApi->message ?? "No se encontró información para el documento.", []];

        }

        return [
            true,
            "La búsqueda se ha efectuado correctamente.",
            $this->formatDocumentLookupData($type, $dataApi->data ?? null),
        ];

    }

    private function formatDocumentLookupData(string $type, ?object $data): array {

        if(!$data) {

            return [];

        }

        if($type === "dni") {

            return [
                "document_number" => $data->numero ?? "",
                "first_name" => $data->nombres ?? "",
                "last_name" => $data->apellido_paterno ?? "",
                "second_last_name" => $data->apellido_materno ?? "",
                "verification_code" => $data->codigo_verificacion ?? "",
            ];

        }

        return [
            "document_number" => $data->ruc ?? "",
            "legal_name" => $data->nombre_o_razon_social ?? "",
            "commercial_name" => $data->nombre_o_razon_social ?? "",
            "address" => $data->direccion_completa ?? "",
        ];

    }

    private function logExternalApiRequest(
        Request $request,
        int $companyId,
        ?int $userId,
        string $result
    ): void {

        if(!Schema::hasTable("external_api_request_logs")) {

            return;

        }

        DB::table("external_api_request_logs")->insert([
            "user_id" => $userId,
            "service" => self::DOCUMENT_LOOKUP_SERVICE,
            "action" => self::DOCUMENT_LOOKUP_ACTION,
            "document_type" => $request->type,
            "document_number" => $request->document_number,
            "result" => $result,
            "ip_address" => $request->ip(),
            "requested_at" => now(),
        ]);

    }

    private function getExternalApiMonthlyUsage(int $companyId): array {

        $threshold = max(1, (int) CompanySettingService::value(
            CompanySettingService::EXTERNAL_API,
            "document_lookup_monthly_warning_threshold",
            80
        ));

        if(!Schema::hasTable("external_api_request_logs")) {

            return [
                "month" => now()->format("Y-m"),
                "used" => 0,
                "warning_threshold" => $threshold,
                "has_warning" => false,
            ];

        }

        $used = DB::table("external_api_request_logs")
            ->where("service", self::DOCUMENT_LOOKUP_SERVICE)
            ->where("action", self::DOCUMENT_LOOKUP_ACTION)
            ->where("requested_at", ">=", now()->startOfMonth())
            ->where("requested_at", "<=", now()->endOfMonth())
            ->count();

        return [
            "month" => now()->format("Y-m"),
            "used" => $used,
            "warning_threshold" => $threshold,
            "has_warning" => $used >= $threshold,
        ];

    }
}
