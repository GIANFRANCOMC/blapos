<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Guest\{GuestCatalogService};
use App\Services\System\Base\{CompanyReferenceDataService};
use Tests\{TestCase};

class ReferenceDataServiceTest extends TestCase {
    public function test_company_reference_data_uses_user_scope_without_a_company_selector(): void {

        $this->assertFalse(method_exists(CompanyReferenceDataService::class, "for"));

        $method = new \ReflectionMethod(CompanyReferenceDataService::class, "forUser");

        $this->assertSame("userId", $method->getParameters()[0]->getName());

    }

    public function test_guest_catalog_uses_the_current_tenant_without_a_company_selector(): void {

        $method = new \ReflectionMethod(GuestCatalogService::class, "publicItems");

        $this->assertSame(0, $method->getNumberOfParameters());

    }
}
