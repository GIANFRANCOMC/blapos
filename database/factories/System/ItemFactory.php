<?php

namespace Database\Factories\System;

use Illuminate\Database\Eloquent\Factories\{Factory};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory {

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {

        return [
            "name" => $this->faker->catchPhrase,
            "description" => $this->faker->sentence,
            "price" => $this->faker->randomFloat(2, 10, 100),
            "currency_id" => 1,
            "status" => $this->faker->randomElement(["active", "inactive"]),
        ];

    }

}
