<?php

declare(strict_types=1);

namespace App\Helpers\System;

use App\Services\System\Organizations\Companies\{CompanySettingService};
use Brick\Math\{BigDecimal, BigRational, RoundingMode};
use Illuminate\Support\{Arr};
use Illuminate\Validation\{Validator};

final class Numbers {

    public const SETTINGS_GROUP = "numeric_validation";
    public const PRECISION_KEY = "decimal_precision";
    public const MAX_PRECISION = 3;
    public const DEFAULTS = [
        self::PRECISION_KEY => 2,
        "default_min_value" => 0,
        "default_max_value" => "999999999999.999",
        "max_file_size_kb" => 4096,
    ];

    public static function isPrecisionSetting(?string $group, ?string $key): bool {

        return strcasecmp($group ?? "", self::SETTINGS_GROUP) === 0
            && strcasecmp($key ?? "", self::PRECISION_KEY) === 0;

    }

    public static function normalizePrecision(mixed $value): int {

        $precision = self::parseInteger($value);

        return $precision === null ? self::DEFAULTS[self::PRECISION_KEY] : max(0, min(self::MAX_PRECISION, $precision));

    }

    public static function allowedPrecision(mixed $value): ?int {

        $precision = self::parseInteger($value);

        return $precision !== null && $precision >= 0 && $precision <= self::MAX_PRECISION ? $precision : null;

    }

    private static function parseInteger(mixed $value): ?int {

        $integer = is_int($value) || is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;

        return $integer === false ? null : $integer;

    }

    private static function settings(): array {

        return CompanySettingService::group(self::SETTINGS_GROUP);

    }

    public static function decimalPrecision(): int {

        return self::settings()[self::PRECISION_KEY];

    }

    public static function minValue(): string {

        return (string) (self::parseDecimal(self::settings()["default_min_value"] ?? null) ?? self::decimal(self::DEFAULTS["default_min_value"]));

    }

    public static function maxValue(): string {

        $limit = self::decimal(self::DEFAULTS["default_max_value"]);
        $configured = self::parseDecimal(self::settings()["default_max_value"] ?? null) ?? $limit;

        return (string) ($configured->isNegative() || $configured->isGreaterThan($limit) ? $limit : $configured);

    }

    public static function maxFileSizeKb(): int {

        return max(1, (int) (self::settings()["max_file_size_kb"] ?? self::DEFAULTS["max_file_size_kb"]));

    }

    public static function inputConfig(): array {

        return [
            "round" => self::decimalPrecision(),
            "maxDecimals" => self::MAX_PRECISION,
            "minValue" => self::minValue(),
            "maxValue" => self::maxValue(),
            "maxSize" => self::maxFileSizeKb(),
        ];

    }

    public static function decimalRule(?int $precision = null): string {

        return "decimal:0,".self::resolvePrecision($precision);

    }

    public static function decimalError(): string {

        return "Usa hasta ".self::decimalPrecision()." decimales.";

    }

    public static function numericRules(float|string|null $min = null, float|string|null $max = null, bool $required = false, bool $decimal = true): array {

        $rules = [$required ? "required" : "nullable", "numeric", "min:".self::decimal($min ?? self::minValue()), "max:".self::decimal($max ?? self::maxValue())];

        if($decimal) {

            $rules[] = self::decimalRule();

        }

        return $rules;

    }

    public static function normalizeDecimalInput(mixed $value): mixed {

        $normalized = is_string($value) ? trim($value) : $value;

        if($normalized === null || $normalized === "") {

            return null;

        }

        $number = self::parseDecimal($normalized);

        return $number === null ? $normalized : (string) self::quantize($number);

    }

    public static function prepareValidator(Validator $validator): Validator {

        $data = $validator->getData();
        $changed = false;

        foreach($validator->getRules() as $attribute => $rules) {

            $value = Arr::get($data, $attribute);

            if(!is_float($value) || !is_finite($value)) {

                continue;

            }

            foreach($rules as $rule) {

                if(is_string($rule) && str_starts_with($rule, "decimal:")) {

                    Arr::set($data, $attribute, (string) self::decimal($value));
                    $changed = true;
                    break;

                }

            }

        }

        if($changed) {

            $validator->setData($data);

        }

        return $validator;

    }

    public static function spreadsheetDecimal(mixed $value, bool $allowZero): ?string {

        if(!is_int($value) && !is_float($value) && !is_string($value)) {

            return null;

        }

        $number = is_float($value) && is_finite($value)
            ? (string) self::decimal($value)
            : str_replace(",", ".", trim((string) $value));
        $precision = self::decimalPrecision();
        $pattern = $precision === 0 ? '/^\d+$/D' : '/^\d+(?:\.\d{1,'.$precision.'})?$/D';

        if(!preg_match($pattern, $number)) {

            return null;

        }

        $value = self::parseDecimal($number);

        return $value !== null && $value->isLessThanOrEqualTo(self::maxValue()) && ($allowZero || !$value->isZero()) ? $number : null;

    }

    public static function decimalStep(?int $precision = null): float {

        return 1 / self::decimalScale($precision);

    }

    public static function decimalScale(?int $precision = null): int {

        return 10 ** self::resolvePrecision($precision);

    }

    public static function round($value, ?int $decimals = null): float {

        return self::quantize($value instanceof BigRational ? $value : self::decimal($value), $decimals)->toFloat();

    }

    public static function sum(iterable $values): float {

        return self::round(self::sumDecimals($values));

    }

    public static function sumText(iterable $values): string {

        return (string) self::quantize(self::sumDecimals($values));

    }

    public static function average(iterable $values): float {

        $total = BigDecimal::zero();
        $count = 0;

        foreach($values as $value) {

            if($value !== null) {

                $total = $total->plus(self::decimal($value));
                $count++;

            }

        }

        return $count === 0 ? 0 : self::divide($total, $count);

    }

    private static function sumDecimals(iterable $values): BigDecimal|BigRational {

        $total = BigDecimal::zero();

        foreach($values as $value) {

            if($value instanceof BigRational || $total instanceof BigRational) {

                $total = $total->toBigRational()->plus($value instanceof BigRational ? $value : self::decimal($value))->simplified();

            }else {

                $total = $total->plus(self::decimal($value));

            }

        }

        return $total;

    }

    public static function compare(mixed $left, mixed $right): int {

        return self::decimal($left)->compareTo(self::decimal($right));

    }

    public static function subtract(mixed $left, mixed $right): float {

        return (float) self::subtractText($left, $right);

    }

    public static function subtractText(mixed $left, mixed $right): string {

        return self::storage(self::decimal($left)->minus(self::decimal($right)));

    }

    public static function multiply(mixed $left, mixed $right): float {

        return self::round(self::decimal($left)->multipliedBy(self::decimal($right)));

    }

    public static function lineTotal(mixed $quantity, mixed $unitPrice): float {

        $amount = self::quantize(self::decimal($quantity))->multipliedBy(self::quantize(self::decimal($unitPrice)));

        return self::quantize($amount)->toFloat();

    }

    public static function taxAmount(mixed $base, array $tax, int $quantity = 1): float {

        $rate = self::storage($tax["rate"] ?? 0);
        $amount = ($tax["calculation_type"] ?? null) === "fixed"
            ? self::multiply($rate, max(1, $quantity))
            : self::percentage($base, $rate);

        return ($tax["operation_type"] ?? null) === "subtraction" && $amount !== 0.0 ? -$amount : $amount;

    }

    public static function commission(mixed $quantity, mixed $unitPrice, string $type, mixed $value): array {

        $rate = self::decimal($value);
        if($rate->isNegative() || !in_array($type, ["fixed", "percentage"], true)) {

            $rate = BigDecimal::zero();

        }elseif($type === "percentage" && $rate->isGreaterThan(100)) {

            $rate = BigDecimal::of(100);

        }

        $rate = self::quantize($rate);
        $quantity = self::quantize(self::decimal($quantity));
        $amount = match ($type) {
            "percentage" => self::percentage($quantity, $rate, self::quantize(self::decimal($unitPrice))),
            "fixed" => self::multiply($quantity, $rate),
            default => 0.0,
        };

        return ["value" => (string) $rate, "amount" => $amount];

    }

    public static function documentTax(array $tax, iterable $lines, int $quantity = 1): array {

        if(($tax["calculation_type"] ?? null) === "fixed") {

            $amount = self::taxAmount(0, $tax, $quantity);

            return ["baseAmount" => 0.0, "amount" => $amount, "totalImpact" => $amount];

        }

        $rate = self::storage($tax["rate"] ?? 0);
        $isSubtraction = ($tax["operation_type"] ?? null) === "subtraction";
        $canBeIncluded = !$isSubtraction && self::compare($rate, 0) > 0;
        $baseAmounts = [];
        $amounts = [];
        $impacts = [];

        foreach($lines as $line) {

            $total = $line["total"] ?? 0;
            if(($line["exempt"] ?? false) || self::compare($total, 0) <= 0) {

                continue;

            }

            if($canBeIncluded && ($line["includesTax"] ?? false)) {

                $base = self::baseFromGross($total, $rate);
                $baseAmounts[] = $base;
                $amounts[] = self::subtract($total, $base);

            }else {

                $amount = self::taxAmount($total, $tax);
                $baseAmounts[] = $total;
                $amounts[] = $amount;
                $impacts[] = $amount;

            }

        }

        return [
            "baseAmount" => self::sum($baseAmounts),
            "amount" => self::sum($amounts),
            "totalImpact" => self::sum($impacts),
        ];

    }

    public static function sumProducts(iterable $pairs): float {

        $total = BigDecimal::zero();

        foreach($pairs as [$left, $right]) {

            $total = $total->plus(self::decimal($left)->multipliedBy(self::decimal($right)));

        }

        return self::round($total);

    }

    public static function divide(mixed $value, mixed $divisor): float {

        return self::proportion($value, 1, $divisor);

    }

    public static function floorQuotient(mixed $value, mixed $divisor): int {

        return self::decimal($value)->dividedBy(self::decimal($divisor), 0, RoundingMode::FLOOR)->toInt();

    }

    public static function proportion(mixed $value, mixed $factor, mixed $divisor): float {

        return self::decimal($value)->multipliedBy(self::decimal($factor))
            ->dividedBy(self::decimal($divisor), self::decimalPrecision(), RoundingMode::HALF_UP)->toFloat();

    }

    public static function percentage(mixed $value, mixed $rate, mixed $factor = 1): float {

        return self::proportion($value, self::decimal($rate)->multipliedBy(self::decimal($factor)), 100);

    }

    public static function ratioOf(iterable $factors, mixed $divisor): BigRational {

        $product = BigDecimal::one();

        foreach($factors as $factor) {

            $product = $product->multipliedBy(self::decimal($factor));

        }

        return $product->toBigRational()->dividedBy(self::decimal($divisor))->simplified();

    }

    public static function baseFromGross(mixed $gross, mixed $rate): float {

        return self::proportion($gross, 100, self::decimal($rate)->plus(100));

    }

    public static function toUnits(mixed $value): int {

        return self::quantize(self::decimal($value))->getUnscaledValue()->toInt();

    }

    public static function fromUnits(int $units): string {

        return (string) BigDecimal::ofUnscaledValue($units, self::decimalPrecision());

    }

    public static function storage(mixed $value): string {

        return self::formatDecimal($value, thousandsSeparator: "");

    }

    public static function storageRow(array $row, array $decimalFields): array {

        foreach($decimalFields as $field) {

            if(isset($row[$field])) {

                $row[$field] = self::storage($row[$field]);

            }

        }

        return $row;

    }

    public static function sqlRound(string $expression): string {

        return "ROUND(".$expression.", ".self::decimalPrecision().")";

    }

    public static function formatDecimal($value, ?int $decimals = null, string $thousandsSeparator = ","): string {

        $formatted = (string) self::quantize(self::decimal($value), $decimals);

        if($thousandsSeparator === "") {

            return $formatted;

        }

        [$whole, $fraction] = array_pad(explode(".", $formatted, 2), 2, null);
        $whole = preg_replace_callback('/\B(?=(\d{3})+(?!\d))/', static fn() => $thousandsSeparator, $whole);

        return $fraction === null ? $whole : $whole.".".$fraction;

    }

    private static function quantize(BigDecimal|BigRational $value, ?int $precision = null): BigDecimal {

        return $value->toScale(self::resolvePrecision($precision), RoundingMode::HALF_UP);

    }

    private static function resolvePrecision(?int $precision): int {

        return $precision === null ? self::decimalPrecision() : max(0, min(self::MAX_PRECISION, $precision));

    }

    private static function decimal(mixed $value): BigDecimal {

        return self::parseDecimal($value) ?? BigDecimal::zero();

    }

    private static function parseDecimal(mixed $value): ?BigDecimal {

        if($value instanceof BigDecimal) {

            return $value;

        }

        if(is_int($value)) {

            return BigDecimal::of($value);

        }

        if(is_float($value) && is_finite($value)) {

            $value = json_encode($value);

        }

        if(!is_string($value)) {

            return null;

        }

        $value = trim($value);

        if(preg_match('/^[+-]?\d{1,3}(?:,\d{3})+(?:\.\d+)?$/D', $value)) {

            $value = str_replace(",", "", $value);

        }

        if(!is_numeric($value) || !is_finite((float) $value) || strlen($value) > 400
            || (preg_match('/e([+-]?\d+)$/iD', $value, $exponent) && abs((int) $exponent[1]) > 400)) {

            return null;

        }

        return BigDecimal::of($value);

    }

    public static function spreadsheetDecimalFormat(bool $groupThousands = true): string {

        $precision = self::decimalPrecision();
        $integerFormat = $groupThousands ? "#,##0" : "0";

        return $precision === 0 ? $integerFormat : $integerFormat.".".str_repeat("0", $precision);

    }

    public static function spreadsheetNumber(mixed $value): float|string {

        $decimal = self::storage($value);
        $significantDigits = trim(str_replace(["-", "."], "", $decimal), "0");

        return strlen($significantDigits) <= 15 ? (float) $decimal : $decimal;

    }

    public static function convertNumberToWords($number): string {

        $amount = self::formatDecimal($number, thousandsSeparator: "");
        [$whole, $fraction] = array_pad(explode(".", ltrim($amount, "-"), 2), 2, "");

        if((float) $amount === 0.0) {

            return "CERO";

        }

        $sign = str_starts_with($amount, "-") ? "MENOS " : "";
        $fractionText = (int) $fraction > 0 ? " CON {$fraction}/".(10 ** strlen($fraction)) : "";

        return $sign.self::wholeNumberToWords((int) $whole).$fractionText." SOLES";

    }

    private static function wholeNumberToWords(int $number): string {

        $words = [
            0 => "CERO", 1 => "UNO", 2 => "DOS", 3 => "TRES", 4 => "CUATRO", 5 => "CINCO",
            6 => "SEIS", 7 => "SIETE", 8 => "OCHO", 9 => "NUEVE", 10 => "DIEZ", 11 => "ONCE",
            12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE", 16 => "DIECISÉIS",
            17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE", 20 => "VEINTE",
            21 => "VEINTIUNO", 22 => "VEINTIDÓS", 23 => "VEINTITRÉS", 24 => "VEINTICUATRO",
            25 => "VEINTICINCO", 26 => "VEINTISÉIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE",
            30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA",
            70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA", 100 => "CIEN",
            200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS",
            500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS",
            800 => "OCHOCIENTOS", 900 => "NOVECIENTOS",
        ];

        if(isset($words[$number])) {

            return $words[$number];

        }

        foreach([1000000000000 => ["UN BILLÓN", "BILLONES"], 1000000 => ["UN MILLÓN", "MILLONES"], 1000 => ["MIL", "MIL"]] as $scale => [$singular, $plural]) {

            if($number >= $scale) {

                $count = intdiv($number, $scale);
                $remainder = $number % $scale;
                $prefix = $count === 1 ? $singular : preg_replace('/UNO$/', 'UN', self::wholeNumberToWords($count))." ".$plural;

                return $prefix.($remainder > 0 ? " ".self::wholeNumberToWords($remainder) : "");

            }

        }

        if($number >= 100) {

            $hundreds = $number < 200 ? "CIENTO" : $words[intdiv($number, 100) * 100];

            return $hundreds." ".self::wholeNumberToWords($number % 100);

        }

        return $words[intdiv($number, 10) * 10]." Y ".$words[$number % 10];

    }

}
