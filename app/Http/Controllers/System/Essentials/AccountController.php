<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Essentials\Account\{UpdateAccountRequest};
use App\Services\System\Essentials\{AccountService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{RedirectResponse};

final class AccountController extends BaseController {

    public function index(): View {

        return view("System/general/Essentials/account/main", [
            "account" => $this->getAuthUser(),
        ]);

    }

    public function update(UpdateAccountRequest $request, AccountService $accountService): RedirectResponse {

        $accountService->update($this->getAuthUser(), $request->validated());

        return redirect()
            ->route("account.index")
            ->with("status", "Tus datos personales se actualizaron correctamente.");

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.account";

    }

}
