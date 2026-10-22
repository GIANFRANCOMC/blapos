<?php

declare(strict_types=1);

namespace Tests\Unit;

use Database\Seeders\DemoPeopleSeeder;
use Database\Seeders\Support\CsvSeedDataset;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class DemoPeopleSourceTest extends TestCase {

    public function test_latest_people_datasets_have_unique_valid_dni_and_editable_fields(): void {

        $seeder = new DemoPeopleSeeder();
        $normalize = new ReflectionMethod($seeder, "normalizePerson");
        $datasets = [
            ["05_clientes_10000.csv", DemoPeopleSeeder::CUSTOMER_COUNT, "CUSTOMER_COLUMNS"],
            ["04_usuarios_10000.csv", DemoPeopleSeeder::COLLABORATOR_COUNT, "USER_COLUMNS"],
        ];

        foreach($datasets as [$filename, $expectedCount, $columnsName]) {

            $path = dirname(__DIR__, 2)."/database/seeders/data/".$filename;
            $headers = (new \ReflectionClass($seeder))->getReflectionConstant($columnsName)->getValue();
            $documents = [];
            $emails = [];
            $count = 0;

            foreach(CsvSeedDataset::rows($path, $headers, $expectedCount) as $number => $row) {

                $person = $normalize->invoke($seeder, $row, 1, $path, $number);
                $documents[$person["document_number"]] = true;
                $emails[$person["email"]] = true;
                $count++;

                $this->assertMatchesRegularExpression('/^\d{8}$/', $person["document_number"]);

            }

            $this->assertSame($expectedCount, $count);
            $this->assertCount($expectedCount, $documents);
            $this->assertCount($expectedCount, $emails);

        }

    }

    public function test_short_dni_is_padded_without_changing_its_document_type(): void {

        $seeder = new DemoPeopleSeeder();
        $normalize = new ReflectionMethod($seeder, "normalizePerson");
        $person = $normalize->invoke($seeder, [
            "identity_document_type_id" => "1",
            "document_number" => "12345",
            "name" => "Persona de prueba",
            "email" => "persona@example.test",
            "phone_number" => "",
            "gender" => "",
            "birthdate" => "",
        ], 7, "personas.csv", 1);

        $this->assertSame(7, $person["identity_document_type_id"]);
        $this->assertSame("00012345", $person["document_number"]);

    }

    public function test_invalid_dni_length_is_rejected(): void {

        $row = [
            "identity_document_type_id" => "1",
            "document_number" => "123456789",
            "name" => "Persona de prueba",
            "email" => "persona@example.test",
            "phone_number" => "",
            "gender" => "",
            "birthdate" => "",
        ];

        $this->expectException(RuntimeException::class);

        $seeder = new DemoPeopleSeeder();

        (new ReflectionMethod($seeder, "normalizePerson"))
            ->invoke($seeder, $row, 1, "personas.csv", 1);

    }

}
