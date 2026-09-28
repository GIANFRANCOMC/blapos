<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Support\{TenantCommandBatchLimit, TenantCommandReport};
use Illuminate\Console\{Command};
use Mockery;
use Tests\{TestCase};

final class TenantCommandReportTest extends TestCase {
    public function test_per_tenant_batch_limit_cannot_be_unbounded(): void {

        $this->assertSame(1, TenantCommandBatchLimit::normalize(0));
        $this->assertSame(500, TenantCommandBatchLimit::normalize("500"));
        $this->assertSame(1000, TenantCommandBatchLimit::normalize(1000000));

    }

    public function test_report_flushes_rows_in_bounded_batches(): void {

        $command = Mockery::mock(Command::class);
        $command->shouldReceive("table")
            ->twice()
            ->withArgs(function(array $headers, array $rows): bool {

                static $batch = 0;
                $batch++;

                return $headers === ["Tenant", "Resultado"]
                    && count($rows) === ($batch === 1 ? 50 : 1);

            });

        $report = new TenantCommandReport($command, ["Tenant", "Resultado"]);

        for($number = 1; $number <= 51; $number++) {

            $report->add(["tenant-{$number}", "OK"]);

        }

        $report->flush();

        $report->flush();

    }
}
