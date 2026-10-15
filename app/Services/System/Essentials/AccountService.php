<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use App\Models\System\Organizations\{User};
use Illuminate\Support\{Arr};

final class AccountService {

    private const EDITABLE_FIELDS = [
        "name",
        "email",
        "phone_number",
        "gender",
        "birthdate",
    ];

    public function update(User $user, array $data): User {

        $user->fill(Arr::only($data, self::EDITABLE_FIELDS));
        $user->updated_by = $user->id;
        $user->save();

        return $user->refresh();

    }

}
