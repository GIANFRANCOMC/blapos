<?php

namespace App\Helpers\System;

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

    public static function startOfDay($date): string {

        return Carbon::parse($date)->startOfDay()->toDateTimeString();

    }

    public static function endOfDay($date): string {

        return Carbon::parse($date)->endOfDay()->toDateTimeString();

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
