<?php

namespace Database\Seeders;

use Illuminate\Database\{Seeder};
use Illuminate\Support\Facades\{Artisan};

final class LandlordTenantDemoSeeder extends Seeder {

    public function run(): void {

        $tenants = [
            [
                "slug" => "demo",
                "commercial_name" => "Demo Gym",
                "legal_name" => "Demo Gym S.A.C.",
                "document_number" => "20600000001",
            ],
            [
                "slug" => "andina",
                "commercial_name" => "Andina Fitness",
                "legal_name" => "Andina Fitness S.A.C.",
                "document_number" => "20600000002",
            ],
            [
                "slug" => "fitcenter",
                "commercial_name" => "Fit Center",
                "legal_name" => "Fit Center S.A.C.",
                "document_number" => "20600000003",
            ],
        ];

        foreach($tenants as $tenant) {

            Artisan::call("tenant:create", [
                "slug" => $tenant["slug"],
                "--commercial-name" => $tenant["commercial_name"],
                "--legal-name" => $tenant["legal_name"],
                "--document-number" => $tenant["document_number"],
                "--force" => true,
            ]);

        }

        $this->call(LandlordDemoAnnouncementsSeeder::class);

    }

}
