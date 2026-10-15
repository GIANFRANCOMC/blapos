<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{PlatformUser};
use Illuminate\Console\{Command};
use Illuminate\Support\Facades\{Hash};
use InvalidArgumentException;

final class CreatePlatformAdministrator extends Command {

    protected $signature = "platform:admin
        {email : Correo del administrador de app}
        {--name=Administrador SaaS : Nombre visible}
        {--password= : Contraseña; se solicita de forma segura si se omite}
        {--inactive : Crea o actualiza el acceso como inactivo}";

    protected $description = "Crea o actualiza un administrador del panel central app.";

    public function handle(): int {

        $password = (string) ($this->option("password") ?: "");

        if($password === "" && $this->input->isInteractive()) {

            $password = (string) $this->secret("Contraseña del administrador SaaS");

        }

        if(strlen($password) < 8) {

            throw new InvalidArgumentException("La contraseña debe tener al menos 8 caracteres.");

        }

        $email = strtolower((string) $this->argument("email"));

        $user = PlatformUser::query()->firstOrNew(["email" => $email]);
        $user->forceFill([
            "name" => (string) $this->option("name"),
            "password" => Hash::make($password),
            "status" => $this->option("inactive") ? "inactive" : "active",
            "session_version" => max(1, (int) $user->session_version + ($user->exists ? 1 : 0)),
        ])->save();

        $this->components->info("Administrador del panel app guardado correctamente.");

        return self::SUCCESS;

    }

}
