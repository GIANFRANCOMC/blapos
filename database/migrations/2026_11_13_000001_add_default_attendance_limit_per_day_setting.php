<?php

declare(strict_types=1);

use App\Services\System\Organizations\Companies\CompanySettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void {

        $records = DB::table("companies")->pluck("id")->map(fn($companyId) => [
            "company_id" => $companyId,
            "group" => CompanySettingService::SUBSCRIPTIONS,
            "key" => CompanySettingService::DEFAULT_ATTENDANCE_LIMIT_PER_DAY_KEY,
            "value" => (string) CompanySettingService::DEFAULT_ATTENDANCE_LIMIT_PER_DAY,
            "description" => "Límite diario de asistencias que se guarda cuando el plan de membresía deja el campo vacío. Deja este valor vacío para guardar el plan sin límite configurado.",
            "value_type" => "integer",
            "status" => "active",
        ])->all();

        DB::table("company_settings")->insertOrIgnore($records);

    }

    public function down(): void {

        // Conservar el parámetro que la empresa puede haber editado.

    }

};
