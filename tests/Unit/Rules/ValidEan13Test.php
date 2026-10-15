<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\System\Catalogs\{ValidEan13};
use Tests\{TestCase};

class ValidEan13Test extends TestCase {

    public function test_it_accepts_a_valid_ean_13_code(): void {

        $messages = [];

        (new ValidEan13())->validate("barcode", "2000000000015", function(string $message) use (&$messages) {

            $messages[] = $message;

        });

        $this->assertSame([], $messages);

    }

    public function test_it_rejects_an_invalid_check_digit(): void {

        $messages = [];

        (new ValidEan13())->validate("barcode", "2000000000012", function(string $message) use (&$messages) {

            $messages[] = $message;

        });

        $this->assertCount(1, $messages);

    }

}
