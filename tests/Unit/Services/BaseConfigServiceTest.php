<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\System\Base\{BaseConfigService};
use stdClass;
use Tests\{TestCase};

final class TestConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "test";

    }

    protected static function cachePages(): array {

        return ["main", "list"];

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data([
            "page" => $page,
        ]);

    }
}

class BaseConfigServiceTest extends TestCase {
    public function test_cache_is_isolated_by_tenant_and_page(): void {

        $main = TestConfigService::getInitParams("main", 1);
        $list = TestConfigService::getInitParams("list", 1);

        $this->assertSame("main", $main->config->page);
        $this->assertSame("list", $list->config->page);
        $this->assertNotSame(
            TestConfigService::cacheKey("main"),
            TestConfigService::cacheKey("list")
        );

    }

    public function test_empty_or_unknown_page_falls_back_to_first_supported_page(): void {

        $empty = TestConfigService::getInitParams("", 1);
        $unknown = TestConfigService::getInitParams("unknown", 1);

        $this->assertSame("main", $empty->config->page);
        $this->assertSame("main", $unknown->config->page);

    }

    public function test_clear_all_cache_forgets_every_supported_page(): void {

        TestConfigService::getInitParams("main", 1);
        TestConfigService::getInitParams("list", 1);

        TestConfigService::clearAllCache();

        $this->assertFalse(cache()->has(TestConfigService::cacheKey("main")));
        $this->assertFalse(cache()->has(TestConfigService::cacheKey("list")));

    }
}
