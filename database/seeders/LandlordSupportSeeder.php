<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\System\Tenancy\{PlatformSupportService};
use Illuminate\Database\{Seeder};
use Illuminate\Support\Facades\{DB};

final class LandlordSupportSeeder extends Seeder {

    public function run(): void {

        DB::connection("landlord")->table("platform_settings")->insertOrIgnore([
            ["key" => "support_email", "value" => PlatformSupportService::DEFAULT_EMAIL, "description" => "Correo de ayuda de Blapos visible para los usuarios."],
            ["key" => "support_phone", "value" => PlatformSupportService::DEFAULT_PHONE, "description" => "Teléfono de ayuda de Blapos visible para los usuarios."],
        ]);

    }

}
