<?php

namespace App\Helpers\System;

/* use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, NumberFormat, Fill};
 */

class UtilitiesExcel {

    public static function columnasExcel() {

        $columnasExcel = [
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
        ];

        return $columnasExcel;

    }

    public static function borderStyle($type = "default") {

        return ["borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "000000"]]]];

    }

    public static function calcularSumaColumnasExcel($array) {

        $suma = 0;

        foreach($array as $key => $value) {

            $suma = $suma + $value["cantidadMerge"]["columnas"];

        }

        return $suma;

    }

    public static function stylesCustom($sheet, $inicioRango, $finRango = null, $value = null, $isBold = false, $isMerge = false, $align = "left", $isBorder = false, $rotate = -1) {

        if($value) {

            $sheet->getCell($inicioRango)->setValue($value);

        }

        if($inicioRango && $finRango && $isBold) {

            $sheet->getStyle($inicioRango.":".$finRango)->getFont()->setBold($isBold);

        }elseif($inicioRango && $isBold) {

            $sheet->getStyle($inicioRango)->getFont()->setBold($isBold);

        }

        if($inicioRango && $finRango && $align) {

            $alignInformacion = explode("-", $align);

            switch($alignInformacion[0]) {

                case "left":
                    $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    break;

                case "center":
                    $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    break;

                default:
                    $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    break;

            }

            if(count($alignInformacion) > 1) {

                switch($alignInformacion[1]) {

                    case "top":
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                        break;

                    case "center":
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        break;

                    default:
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
                        break;

                }

            }

        }elseif($inicioRango && $align) {

            $alignInformacion = explode("-", $align);

            switch($alignInformacion[0]) {

                case "left":
                    $sheet->getStyle($inicioRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    break;

                case "center":
                    $sheet->getStyle($inicioRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    break;

                default:
                    $sheet->getStyle($inicioRango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    break;

            }

            if(count($alignInformacion) > 1) {

                switch($alignInformacion[1]) {

                    case "top":
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                        break;

                    case "center":
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        break;

                    default:
                        $sheet->getStyle($inicioRango.":".$finRango)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
                        break;

                }

            }

        }

        if($inicioRango && $finRango && $isMerge) {

            $sheet->mergeCells($inicioRango.":".$finRango);

        }

        if($isBorder) {

            $borderStyleDefault = self::borderStyle();

            if($inicioRango && $finRango && $isBorder) {

                $sheet->getStyle($inicioRango.":".$finRango)->applyFromArray($borderStyleDefault);

            }elseif($inicioRango && $isBorder) {

                $sheet->getStyle($inicioRango)->applyFromArray($borderStyleDefault);

            }

        }

        if($rotate >= 0) {

            $sheet->getStyle($inicioRango)->getAlignment()->setTextRotation($rotate);

        }

    }

}
