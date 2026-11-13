<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Services\System\Organizations\Companies\{CompanySettingService};
use Closure;
use Illuminate\Support\Facades\DB;

final class InternalCodeService {

    public const WHITESPACE_PATTERN = '/[\s\p{Z}]/u';

    public const WITHOUT_WHITESPACE = 'regex:/\A[^\s\p{Z}]+\z/u';

    public static function prefix(string $entity): string {

        $prefix = CompanySettingService::value(
            CompanySettingService::INTERNAL_CODE_PREFIXES,
            $entity,
            ""
        );

        return strtoupper(trim((string) $prefix, " \t\n\r\0\x0B-"));

    }

    public static function acceptsCode(string $entity, string $code, ?int $id = null): bool {

        $prefix = self::prefix($entity);
        if($prefix === "" || (str_starts_with($code, "{$prefix}-") && strlen($code) > strlen($prefix) + 1)) {
            return true;
        }

        if($id === null) return false;

        $table = match ($entity) {
            "product", "service", "subscription" => "items",
            "brand" => "brands",
            "category" => "categories",
            "branch" => "branches",
            "asset" => "assets",
        };

        $storedCode = DB::table($table)->where("id", $id)
            ->when($table === "items", fn($query) => $query->where("type", $entity))
            ->value("internal_code");

        return $storedCode === $code;

    }

    public static function prefixValidationMessage(string $entity): string {

        return "Ingresa el código interno completo con el prefijo vigente ".self::prefix($entity)."- y un valor después del guion.";

    }

    public static function prefixRule(string $entity, ?int $id = null): Closure {

        return static function(string $attribute, string $code, Closure $fail) use ($entity, $id): void {
            if(!self::acceptsCode($entity, $code, $id)) {
                $fail(self::prefixValidationMessage($entity));
            }
        };

    }

}
