<?php

namespace App\Helpers\System;

use App\Services\System\Organizations\Companies\{CompanySettingService};
use Carbon\{Carbon};
use DateTime;
use stdClass;

class Utilities {

    public static $per_page_default = 10;

    public static $per_page_max = 1000;

    public static $messages = [
        "422" => "Error al validar.",
    ];

    public static function getOwnerApp() {

        $data = [
            "commercial_name" => "BLAPOS",
            "web" => "https://blapos.com",
            "color_palette" => [
                "primary" => "#2899E5",
                "secondary" => "#1A1A35",
                "text_by_primary" => "#FFFFFF",
            ],
            "assets" => [
                "img" => [
                    "logotype" => "System/assets/img/utils/owner_app/logotype.png",
                    "combinationmark" => "System/assets/img/utils/owner_app/combinationmark.png",
                    "logomark" => "System/assets/img/utils/owner_app/logomark.png",
                    "login_image" => "System/assets/img/utils/owner_app/login_image.png",
                ],
            ],
        ];

        return json_decode(json_encode($data));

    }

    public static function getDefaultData() {

        $result = new stdClass();

        return $result;

    }

    public static function isDefined($valor) {

        return isset($valor) && !empty($valor);

    }

    public static function getValues($array, $type, $code) {

        $result = null;

        if(in_array($type, ["all"])) {

            $result = $array;

        }elseif(in_array($type, ["first"])) {

            $filter = array_filter($array, function($e) use ($code) {

                return $e["code"] === $code;

            });

            $result = count($filter) > 0 ? reset($filter) : null;

        }

        return $result;

    }

    public static function decimalPrecision(): int {

        return CompanySettingService::decimalPrecision();

    }

    public static function decimalStep(): float {

        return 10 ** -self::decimalPrecision();

    }

    public static function round($value, $decimals = null) {

        return round((float) $value, $decimals ?? self::decimalPrecision(), PHP_ROUND_HALF_UP);

    }

    public static function formatDecimal($value, ?int $decimals = null, string $thousandsSeparator = ","): string {

        return number_format(
            (float) ($value ?? 0),
            $decimals ?? self::decimalPrecision(),
            ".",
            $thousandsSeparator
        );

    }

    public static function spreadsheetDecimalFormat(bool $groupThousands = true): string {

        $precision = self::decimalPrecision();
        $integerFormat = $groupThousands ? "#,##0" : "0";

        return $precision === 0 ? $integerFormat : $integerFormat.".".str_repeat("0", $precision);

    }

    public static function startOfDay($date): string {

        return Carbon::parse($date)->startOfDay()->toDateTimeString();

    }

    public static function endOfDay($date): string {

        return Carbon::parse($date)->endOfDay()->toDateTimeString();

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

    public static function getWordSearch($word, $type = "like") {

        return "%".trim($word ?? "")."%";

    }

    public static function generateCode($length = 12) {

        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $randomString = "";

        for($i = 0; $i < $length; $i++) {

            $randomIndex = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$randomIndex];

        }

        return $randomString;

    }

    public static function isValidDateFormat($date, $format = "Y-m-d") {

        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;

    }

}
