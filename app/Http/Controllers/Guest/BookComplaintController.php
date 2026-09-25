<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\{Controller};
use App\Http\Requests\Guest\{StoreBookComplaintRequest};
use App\Models\Guest\{BookComplaint, Branch, IdentityDocumentType};
use App\Services\System\Tenancy\{TenantStoragePath};
use Carbon\{Carbon};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\{Str};
use Jenssegers\Agent\{Agent};
use stdClass;

final class BookComplaintController extends Controller {
    public function initParams(Request $request) {

        $initParams = new stdClass();
        $config = new stdClass();

        if(in_array((string) $request->input("page"), ["main"], true)) {

            $config->bookComplaints = (object) [
                "types" => BookComplaint::getTypes(),
                "statuses" => BookComplaint::getStatuses(),
            ];

            $config->identityDocumentTypes = (object) [
                "records" => IdentityDocumentType::query()
                    ->whereIn("id", [1, 2, 4])
                    ->get(),
            ];

            $config->branches = (object) [
                "records" => Branch::query()
                    ->where("status", "active")
                    ->orderBy("name")
                    ->get(["id", "name", "address"]),
            ];

        }

        $initParams->config = $config;

        $initParams->bool = true;

        return $initParams;

    }

    public function index(Request $request) {

        $company = $request->get("company");

        return view("Guest/general/book_complaints/main", [
            "company" => $company,
            "meta" => [
                "title" => "Libro de reclamaciones | {$company->commercial_name}",
                "description" => "Registra o consulta una queja, reclamo o sugerencia enviada a {$company->commercial_name}.",
                "image" => $company->combinationmark ?: $company->logotype ?: $company->logomark,
            ],
        ]);

    }

    public function store(StoreBookComplaintRequest $request) {

        $company = $request->get("company");
        $agent = new Agent();
        $todaySubmissions = BookComplaint::query()
            ->where("submitted_ip", $request->ip())
            ->whereBetween("created_at", [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
            ->count();

        if($todaySubmissions >= (int) config("public_access.complaints.per_day")) {

            return response()->json([
                "bool" => false,
                "msg" => "Alcanzaste el límite diario de solicitudes desde este dispositivo.",
            ], 429);

        }

        $storedPaths = [];

        try {

            $bookComplaint = DB::transaction(function() use ($request, $company, $agent, &$storedPaths) {

                $payload = collect($request->validated())
                    ->except(["attachments", "cf-turnstile-response", "website"])
                    ->all();

                $record = BookComplaint::create([
                    ...$payload,
                    "admin_response" => null,
                    "public_response" => null,
                    "tracking_code" => $this->uniqueTrackingCode((int) $company->id),
                    "submitted_ip" => $request->ip(),
                    "submitted_user_agent" => $request->userAgent(),
                    "submitted_platform" => $agent->platform(),
                    "submitted_browser" => $agent->browser(),
                    "status" => "pending",
                    "created_at" => now(),
                    "created_by" => null,
                ]);

                DB::table("book_complaint_status_histories")->insert([
                    "book_complaint_id" => $record->id,
                    "changed_by" => null,
                    "previous_status" => null,
                    "new_status" => "pending",
                    "note" => "Solicitud recibida desde el canal público.",
                    "changed_at" => now(),
                ]);

                foreach($request->file("attachments", []) as $file) {

                    $storedName = Str::uuid()->toString().".".$file->guessExtension();
                    $path = $file->storeAs(
                        TenantStoragePath::for("complaints/{$record->id}"),
                        $storedName,
                        "local"
                    );

                    $storedPaths[] = $path;

                    DB::table("book_complaint_attachments")->insert([
                        "book_complaint_id" => $record->id,
                        "file_name" => mb_substr($file->getClientOriginalName(), 0, 255),
                        "file_path" => $path,
                        "mime_type" => mb_substr((string) $file->getMimeType(), 0, 100),
                        "file_size" => (int) $file->getSize(),
                        "created_at" => now(),
                    ]);

                }

                return $record;

            });

        }catch(\Throwable $exception) {

            Storage::disk("local")->delete($storedPaths);

            throw $exception;

        }

        return response()->json([
            "bool" => true,
            "msg" => "Tu solicitud fue registrada correctamente.",
            "tracking_code" => $bookComplaint->tracking_code,
        ], 201);

    }

    public function status(Request $request, string $trackingCode) {

        $company = $request->get("company");
        $complaint = BookComplaint::query()
            ->where("tracking_code", Str::upper($trackingCode))
            ->first();

        if(!$complaint) {

            return response()->json([
                "bool" => false,
                "msg" => "No encontramos una solicitud con ese código. Revisa que lo hayas escrito correctamente.",
            ], 404);

        }

        return response()->json([
            "bool" => true,
            "msg" => "Solicitud encontrada.",
            "data" => [
                "tracking_code" => $complaint->tracking_code,
                "type" => $complaint->formatted_type,
                "status" => $complaint->formatted_status,
                "public_response" => $complaint->public_response,
                "responded_at" => $complaint->responded_at,
                "created_at" => $complaint->created_at,
            ],
        ]);

    }

    private function uniqueTrackingCode(int $companyId): string {

        do {

            $code = Str::upper(Str::random(12));

        } while(BookComplaint::query()
            ->where("tracking_code", $code)
            ->exists());

        return $code;

    }
}
