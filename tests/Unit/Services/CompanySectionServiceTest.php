<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Database\Eloquent\{Collection};
use Illuminate\Support\Facades\{Cache};
use Tests\{TestCase};

class CompanySectionServiceTest extends TestCase {
    protected function tearDown(): void {

        Cache::forget(CompanySectionService::cacheKey());
        Cache::forget(CompanySectionService::cacheKey(10));

        parent::tearDown();

    }

    public function test_it_returns_cached_sections_without_requerying(): void {

        $sections = new Collection([(object) ["id" => 1]]);

        Cache::put(CompanySectionService::cacheKey(), $sections, 1800);

        $result = CompanySectionService::getSections();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->id);

    }

    public function test_clear_cache_forgets_company_sections(): void {

        $cacheKey = CompanySectionService::cacheKey();

        Cache::put($cacheKey, new Collection(), 1800);
        CompanySectionService::clearCache();

        $this->assertFalse(Cache::has($cacheKey));

    }

    public function test_role_cache_keys_are_isolated_inside_the_tenant_namespace(): void {

        $this->assertNotSame(
            CompanySectionService::cacheKey(),
            CompanySectionService::cacheKey(10)
        );

        $this->assertStringEndsWith(":role:10", CompanySectionService::cacheKey(10));

    }
}
