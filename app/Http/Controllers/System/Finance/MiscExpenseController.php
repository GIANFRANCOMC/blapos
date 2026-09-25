<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Models\System\Finance\{CashSession, MiscExpenseCategory};
use App\Models\System\General\{Currency};
use App\Services\System\Base\{CompanyReferenceDataService};
use App\Services\System\Finance\{MiscExpenseService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Validator};
use Illuminate\Validation\{Rule};

final class MiscExpenseController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Finance.misc_expenses";

    public function index() {

        return view("System/general/Finance/misc_expenses/main");

    }

    public function initParams(): JsonResponse {

        $references = CompanyReferenceDataService::forUser($this->getUserId());
        $cashSessions = CashSession::query()
            ->with("register:id,name")
            ->where("status", "open");

        $allowedCashRegisterIds = $references->allowedCashRegisterIds();

        if($allowedCashRegisterIds !== null) {

            $cashSessions->whereIn("cash_register_id", $allowedCashRegisterIds);

        }

        return response()->json([
            "bool" => true,
            "data" => [
                "branches" => $references->activeBranches(),
                "cashSessions" => $cashSessions
                    ->orderByDesc("opened_at")
                    ->get(),
                "paymentMethods" => $references->paymentMethodsFor("purchase"),
                "currencies" => Currency::query()
                    ->where("status", "active")
                    ->orderBy("code")
                    ->get(),
                "categories" => MiscExpenseCategory::query()
                    ->where("status", "active")
                    ->orderBy("name")
                    ->get(),
                "users" => $references->users(),
            ],
        ]);

    }

    public function list(Request $request) {

        return MiscExpenseService::query(
            $this->getCompanyId(),
            [
                "word" => $request->input("word"),
                "status" => $request->input("status"),
                "branch_id" => $request->input("branch_id"),
            ],
            $this->getUserId()
        )->paginate($this->getPerPage($request, Utilities::$per_page_default));

    }

    public function store(Request $request): JsonResponse {

        $companyId = $this->getCompanyId();
        $validator = Validator::make($request->all(), [
            "branch_id" => ["nullable", "integer", Rule::exists("branches", "id")],
            "cash_session_id" => ["nullable", "integer", Rule::exists("cash_sessions", "id")->where("status", "open")],
            "payment_method_id" => ["nullable", "integer", Rule::exists("payment_methods", "id")],
            "currency_id" => ["required", "integer", Rule::exists("currencies", "id")->where("status", "active")],
            "misc_expense_category_id" => ["nullable", "integer", Rule::exists("misc_expense_categories", "id")->where("status", "active")],
            "responsible_user_id" => ["nullable", "integer", Rule::exists("users", "id")->where("status", "active")],
            "expense_date" => ["required", "date"],
            "reference" => ["nullable", "string", "max:100"],
            "concept" => ["required", "string", "max:255"],
            "amount" => ["required", "numeric", "gt:0", "decimal:0,".Utilities::$inputs["round"]],
            "description" => ["nullable", "string", "max:2000"],
            "observation" => ["nullable", "string", "max:2000"],
        ], [
            "required" => "Campo obligatorio.",
            "numeric" => "Ingresa un número válido.",
            "gt" => "Debe ser mayor que cero.",
            "decimal" => "Usa hasta ".Utilities::$inputs["round"]." decimales.",
            "max" => "Supera la longitud permitida.",
        ]);

        if($validator->fails()) {

            return response()->json(["bool" => false, "errors" => $validator->errors()], 422);

        }

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Gasto registrado correctamente.",
                "data" => MiscExpenseService::create($this->getCompanyId(), $this->getUserId(), $validator->validated()),
            ], 201);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function cancel(int $id): JsonResponse {

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Gasto anulado correctamente.",
                "data" => MiscExpenseService::cancel($this->getCompanyId(), $id, $this->getUserId()),
            ]);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
