<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SeederInitializationTest extends TestCase {

    public function test_seeders_do_not_update_existing_records(): void {

        foreach(glob(dirname(__DIR__, 2)."/database/seeders/*.php") as $path) {

            $source = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression(
                '/->(?:update|upsert|updateOrInsert|updateOrCreate)\s*\(/',
                $source,
                basename($path)." debe insertar sin modificar registros existentes."
            );

        }

    }

}
