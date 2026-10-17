<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogs;

use App\Exports\System\Catalogs\Offerings\{OfferingImportTemplateExport, OfferingListExport};
use App\Models\System\Catalogs\{Item};
use PHPUnit\Framework\{TestCase};

final class OfferingSpreadsheetTest extends TestCase {

    public function test_service_template_only_requests_basic_service_fields(): void {

        $headings = (new OfferingImportTemplateExport(Item::TYPE_SERVICE))->headings();

        $this->assertSame("Nombre", $headings[0]);
        $this->assertContains("Duración estimada minutos", $headings);
        $this->assertNotContains("Stock inicial", $headings);

    }

    public function test_subscription_template_requires_duration_value_and_type(): void {

        $headings = (new OfferingImportTemplateExport(Item::TYPE_SUBSCRIPTION))->headings();

        $this->assertContains("Duración valor", $headings);
        $this->assertContains("Duración tipo", $headings);
        $this->assertNotContains("Duración estimada minutos", $headings);

    }

    public function test_exports_keep_type_specific_columns_without_inventory(): void {

        $service = (new OfferingListExport(Item::TYPE_SERVICE))->headings();
        $subscription = (new OfferingListExport(Item::TYPE_SUBSCRIPTION))->headings();

        $this->assertContains("Duración estimada (minutos)", $service);
        $this->assertNotContains("Asistencias por día", $service);
        $this->assertContains("Asistencias por día", $subscription);
        $this->assertContains("Beneficios", $subscription);
        $this->assertNotContains("Stock total", $service);
        $this->assertNotContains("Stock total", $subscription);

    }

}
