<?php

declare(strict_types=1);

namespace App\Support\System\Warehouses;

use InvalidArgumentException;

final class InventoryOperationTemplate {

    private const OPERATIONS = [
        "physical_count" => ["label" => "Toma física", "amount_heading" => "Saldo contado", "amount_key" => "saldo_contado", "has_cost" => false],
        "manual_entry" => ["label" => "Entrada manual", "amount_heading" => "Cantidad de entrada", "amount_key" => "cantidad_de_entrada", "has_cost" => true],
        "customer_return" => ["label" => "Devolución de cliente", "amount_heading" => "Cantidad devuelta", "amount_key" => "cantidad_devuelta", "has_cost" => true],
        "manual_exit" => ["label" => "Salida manual", "amount_heading" => "Cantidad de salida", "amount_key" => "cantidad_de_salida", "has_cost" => false],
        "supplier_return" => ["label" => "Devolución a proveedor", "amount_heading" => "Cantidad al proveedor", "amount_key" => "cantidad_al_proveedor", "has_cost" => false],
    ];

    public static function for(string $code): array {

        return self::OPERATIONS[$code]
            ?? throw new InvalidArgumentException("El tipo de operación no tiene plantilla disponible.");

    }

    public static function codes(): array {

        return array_keys(self::OPERATIONS);

    }

}
