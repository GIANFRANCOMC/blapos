<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Generator;
use RuntimeException;

final class CsvSeedDataset {

    public static function rows(string $path, array $expectedHeaders, int $expectedCount): Generator {

        $handle = fopen($path, "rb");

        if($handle === false) {

            throw new RuntimeException("No se pudo abrir el dataset: ".basename($path));

        }

        try {

            $headers = fgetcsv($handle);

            if(!is_array($headers)) {

                throw new RuntimeException("El dataset está vacío: ".basename($path));

            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', "", $headers[0]);

            if($headers !== $expectedHeaders) {

                throw new RuntimeException("Las columnas no coinciden con el dataset esperado: ".basename($path));

            }

            $count = 0;

            while(($values = fgetcsv($handle)) !== false) {

                $count++;

                if(count($values) !== count($headers)) {

                    throw new RuntimeException("Columnas incompletas en ".basename($path).", fila ".($count + 1).".");

                }

                yield $count => array_combine($headers, $values);

            }

            if($count !== $expectedCount) {

                throw new RuntimeException(
                    "El dataset ".basename($path)." debe tener {$expectedCount} registros; se encontraron {$count}."
                );

            }

        }finally {

            fclose($handle);

        }

    }

}
