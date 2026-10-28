<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\System\Tenancy\{TenantAnnouncement, TenantDatabase};
use App\Services\System\Tenancy\TenantAnnouncementService;
use Illuminate\Database\Seeder;

final class LandlordDemoAnnouncementsSeeder extends Seeder {

    public function run(): void {

        $tenant = TenantDatabase::query()->where("slug", "demo")->first();

        if(!$tenant) return;

        $announcements = [
            ["title" => "Bienvenido a Blapos", "message" => "Explora los módulos disponibles desde el menú lateral.", "severity" => "success"],
            ["title" => "Accesos rápidos", "message" => "Encuentra tus funciones habituales en la pantalla de inicio.", "severity" => "info"],
            ["title" => "Búsqueda de módulos", "message" => "Usa el buscador superior para encontrar opciones de navegación.", "severity" => "info"],
            ["title" => "Mi cuenta", "message" => "Revisa tus datos personales desde el menú de usuario.", "severity" => "info"],
            ["title" => "Datos de la empresa", "message" => "Consulta la información de tu empresa en la pantalla de inicio.", "severity" => "info"],
            ["title" => "Centro de ayuda", "message" => "Los datos de contacto de soporte están disponibles al final del inicio.", "severity" => "success"],
            ["title" => "Menú de mundos", "message" => "Cambia entre las áreas disponibles desde la barra lateral.", "severity" => "info"],
            ["title" => "Submódulos", "message" => "Selecciona una opción del menú para acceder a su contenido.", "severity" => "info"],
            ["title" => "Clientes", "message" => "Consulta el módulo de clientes desde los accesos disponibles.", "severity" => "info"],
            ["title" => "Servicios en curso", "message" => "Revisa el seguimiento de servicios desde Inicio.", "severity" => "info"],
            ["title" => "Inventario", "message" => "Consulta existencias y movimientos desde el área de inventario.", "severity" => "info"],
            ["title" => "Traslados", "message" => "El área de inventario reúne los traslados entre almacenes.", "severity" => "info"],
            ["title" => "Guías", "message" => "Consulta las guías disponibles desde el menú de inventario.", "severity" => "info"],
            ["title" => "Datos actualizados", "message" => "Comprueba la información antes de usarla en tus operaciones.", "severity" => "warning"],
            ["title" => "Avisos de la plataforma", "message" => "Esta sección muestra comunicaciones vigentes para tu empresa.", "severity" => "info"],
        ];

        foreach($announcements as $announcement) {

            TenantAnnouncement::query()->firstOrCreate(
                ["tenant_database_id" => $tenant->id, "title" => $announcement["title"]],
                [
                    "message" => $announcement["message"],
                    "severity" => $announcement["severity"],
                    "status" => "active",
                    "dismissible" => false,
                ]
            );

        }

        app(TenantAnnouncementService::class)->forgetFor($tenant);

    }

}
