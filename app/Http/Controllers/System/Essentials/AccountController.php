<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Essentials\Account\{UpdateAccountRequest};
use App\Models\System\Organizations\User;
use App\Services\System\Essentials\{AccountService};
use App\Services\System\Tenancy\TenantCompanyContext;
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};

final class AccountController extends BaseController {

    public function index(Request $request): View|JsonResponse {

        if(!$request->expectsJson()) {

            return view("System/general/Essentials/account/main");

        }

        $account = $this->getAuthUser()->loadMissing("role");
        $company = app(TenantCompanyContext::class)->get()->loadMissing("identityDocumentType");

        return response()->json([
            "account" => $this->accountData($account),
            "company" => [
                "name" => $company->legal_name ?: $company->commercial_name ?: "Empresa",
                "document_type" => $company->identityDocumentType?->name,
                "document_number" => $company->document_number,
            ],
        ]);

    }

    public function update(UpdateAccountRequest $request, AccountService $accountService): RedirectResponse|JsonResponse {

        $account = $accountService->update($this->getAuthUser(), $request->validated());

        if($request->expectsJson()) {

            return response()->json([
                "bool" => true,
                "msg" => "Tus datos personales se actualizaron correctamente.",
                "account" => $this->accountData($account->loadMissing("role")),
            ]);

        }

        return redirect()
            ->route("account.index")
            ->with("status", "Tus datos personales se actualizaron correctamente.");

    }

    private function accountData(User $account): array {

        return [
            "name" => $account->name,
            "email" => $account->email,
            "phone_number" => $account->phone_number,
            "gender" => $account->gender,
            "birthdate" => $account->birthdate,
            "role" => $account->role?->name,
        ];

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.account";

    }

}
