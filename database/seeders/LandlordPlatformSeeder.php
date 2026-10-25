<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\{Seeder};
use Illuminate\Support\Facades\{DB, Hash};
use RuntimeException;

final class LandlordPlatformSeeder extends Seeder {

    public function run(): void {

        $name = trim((string) config("tenancy.platform_admin.name"));
        $email = strtolower(trim((string) config("tenancy.platform_admin.email")));
        $password = (string) config("tenancy.platform_admin.password");

        if($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {

            throw new RuntimeException("Configura PLATFORM_ADMIN_NAME, PLATFORM_ADMIN_EMAIL y una contraseña de al menos ocho caracteres.");

        }

        if(app()->environment("production") && $password === "Admin12345!") {

            throw new RuntimeException("PLATFORM_ADMIN_PASSWORD debe cambiarse antes de inicializar landlord en producción.");

        }

        $this->call(LandlordSupportSeeder::class);

        DB::connection("landlord")->table("platform_users")->insertOrIgnore([
            "name" => $name,
            "email" => $email,
            "password" => Hash::make($password),
            "status" => "active",
            "session_version" => 1,
        ]);

    }

}
