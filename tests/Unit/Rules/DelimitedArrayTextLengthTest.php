<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\System\Catalogs\{DelimitedArrayTextLength};
use Tests\{TestCase};

class DelimitedArrayTextLengthTest extends TestCase {
    public function test_it_accepts_one_hundred_characters_including_separators(): void {

        $messages = $this->validateText([str_repeat("A", 49), str_repeat("B", 49)]);

        $this->assertSame([], $messages);

    }

    public function test_it_rejects_text_longer_than_one_hundred_characters(): void {

        $messages = $this->validateText([str_repeat("A", 50), str_repeat("B", 49)]);

        $this->assertSame(["El campo beneficios no puede superar 100 caracteres en total."], $messages);

    }

    public function test_it_counts_accented_characters_as_single_characters(): void {

        $messages = $this->validateText([str_repeat("ñ", 100)]);

        $this->assertSame([], $messages);

    }

    private function validateText(array $items): array {

        $messages = [];

        (new DelimitedArrayTextLength(100, "beneficios"))->validate("benefits", $items, function(string $message) use (&$messages) {

            $messages[] = $message;

        });

        return $messages;

    }
}
