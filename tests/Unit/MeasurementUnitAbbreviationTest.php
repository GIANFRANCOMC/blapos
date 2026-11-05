<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\System\General\MeasurementUnit;
use PHPUnit\Framework\TestCase;

final class MeasurementUnitAbbreviationTest extends TestCase {

    public function test_abbreviation_is_stored_without_an_implicit_default(): void {

        $unit = new MeasurementUnit([
            "code" => "BOX",
            "plural_name" => "CAJAS",
            "abbreviation" => "CJ",
        ]);

        $this->assertSame("CJ", $unit->abbreviation);
        $this->assertNull((new MeasurementUnit())->abbreviation);

    }

}
