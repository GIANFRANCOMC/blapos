<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\StockManagement;

use Dompdf\Adapter\CPDF;
use Illuminate\Support\Carbon;

/** Dibuja el reporte fila por fila: no construye una tabla HTML gigante en Dompdf. */
final class StockAlertsPdfRenderer {

    private const LEFT = 32.0;
    private const RIGHT = 810.0;
    private const TABLE_BOTTOM = 548.0;
    private const COLUMNS = [28.0, 286.0, 90.0, 90.0, 90.0, 116.0, 78.0];

    private const NAVY = [0.086, 0.141, 0.282];
    private const BLUE = [0.043, 0.388, 0.663];
    private const HEADER = [0.227, 0.314, 0.478];
    private const TEXT = [0.094, 0.137, 0.259];
    private const MUTED = [0.37, 0.44, 0.55];
    private const BORDER = [0.86, 0.90, 0.95];
    private const SURFACE = [0.95, 0.97, 0.99];
    private const WHITE = [1.0, 1.0, 1.0];
    private const WARNING = [0.66, 0.35, 0.03];

    private CPDF $canvas;
    private string $regularFont;
    private string $boldFont;

    public function render(object $warehouse, ?object $company, iterable $alerts, int $total, Carbon $generatedAt): string {

        $this->canvas = new CPDF("a4", "landscape");
        $fonts = $this->canvas->get_dompdf()->getFontMetrics();
        $this->regularFont = $fonts->getFont("DejaVu Sans", "normal");
        $this->boldFont = $fonts->getFont("DejaVu Sans", "bold");

        $branchName = (string) ($warehouse->branch?->name ?: "Sin sede asignada");
        $warehouseName = (string) $warehouse->name;
        $companyName = (string) ($company?->legal_name ?: ($company?->commercial_name ?: "Control de inventario"));

        $this->drawFirstPage($branchName, $warehouseName, $companyName, (string) ($company?->document_number ?? ""), $total, $generatedAt);
        $rowTop = 174.0;
        $index = 0;

        foreach($alerts as $alert) {

            $index++;
            $nameLines = $this->wrapText((string) $alert->product_name, 274, 8.2, $this->boldFont);
            $identifiers = "Cód. interno: ".($alert->internal_code ?: "—")."   ·   Cód. barras: ".($alert->barcode ?: "—");
            $codeLines = $this->wrapText($identifiers, 274, 6.8, $this->regularFont);
            $rowHeight = max(38.0, 8.0 + count($nameLines) * 10.0 + count($codeLines) * 8.0 + 6.0);

            if($rowTop + $rowHeight > self::TABLE_BOTTOM) {

                $this->canvas->new_page();
                $rowTop = $this->drawContinuationPage($branchName, $warehouseName);

            }

            $this->drawRow($alert, $index, $rowTop, $rowHeight, $nameLines, $codeLines);
            $rowTop += $rowHeight;

        }

        if($total === 0) {

            $this->text(self::LEFT + 12, $rowTop + 16, "No hay alertas abiertas en este almacén.", 9, self::MUTED);

        }

        $this->drawFooters($branchName, $warehouseName, $generatedAt);

        return $this->canvas->output();

    }

    private function drawFirstPage(string $branch, string $warehouse, string $company, string $document, int $total, Carbon $generatedAt): void {

        $this->canvas->filled_rectangle(0, 0, 842, 4, self::BLUE);
        $this->text(self::LEFT, 28, "BLAPOS", 11, self::BLUE, true);
        $this->text(self::LEFT, 46, $this->fitText($company, 430, 9, $this->boldFont), 9, self::NAVY, true);

        if($document !== "") {

            $this->text(self::LEFT, 61, "RUC ".$document, 7.5, self::MUTED);

        }

        $this->rightText(self::RIGHT, 26, "Alertas de stock", 20, self::NAVY, true);
        $this->rightText(self::RIGHT, 56, "Productos en mínimo o sin existencias · ".$generatedAt->format("d/m/Y H:i"), 7.8, self::MUTED);

        $this->summaryCard(self::LEFT, 87, 226, "SEDE", $branch);
        $this->summaryCard(self::LEFT + 234, 87, 294, "ALMACÉN", $warehouse);
        $this->summaryCard(self::LEFT + 536, 87, 242, "ALERTAS ABIERTAS", number_format($total, 0, ".", ","), true);
        $this->drawTableHeader(149);

    }

    private function drawContinuationPage(string $branch, string $warehouse): float {

        $this->canvas->filled_rectangle(0, 0, 842, 4, self::BLUE);
        $this->text(self::LEFT, 24, "Alertas de stock", 13, self::NAVY, true);
        $this->rightText(self::RIGHT, 28, $this->fitText($branch." / ".$warehouse, 430, 8, $this->regularFont), 8, self::MUTED);
        $this->drawTableHeader(52);

        return 77.0;

    }

    private function summaryCard(float $x, float $y, float $width, string $label, string $value, bool $isCount = false): void {

        $this->canvas->filled_rectangle($x, $y, $width, 49, self::SURFACE);
        $this->canvas->rectangle($x, $y, $width, 49, self::BORDER, 0.5);
        $this->text($x + 11, $y + 8, $label, 7, self::MUTED, true);
        $this->text($x + 11, $y + 23, $this->fitText($value, $width - 22, $isCount ? 15 : 9, $this->boldFont), $isCount ? 15 : 9, $isCount ? self::BLUE : self::NAVY, true);

    }

    private function drawTableHeader(float $top): void {

        $this->canvas->filled_rectangle(self::LEFT, $top, self::RIGHT - self::LEFT, 25, self::HEADER);
        $labels = ["#", "PRODUCTO E IDENTIFICACIÓN", "STOCK ACTUAL", "STOCK MÍNIMO", "FALTANTE", "DETECTADA", "ESTADO"];
        $x = self::LEFT;

        foreach(self::COLUMNS as $index => $width) {

            $this->centerText($x, $top + 8, $width, $labels[$index], 7, self::WHITE, true);
            $x += $width;

        }

    }

    private function drawRow(object $alert, int $index, float $top, float $height, array $nameLines, array $codeLines): void {

        if($index % 2 === 0) {

            $this->canvas->filled_rectangle(self::LEFT, $top, self::RIGHT - self::LEFT, $height, self::SURFACE);

        }

        $this->canvas->line(self::LEFT, $top + $height, self::RIGHT, $top + $height, self::BORDER, 0.45);
        $this->centerText(self::LEFT, $top + 11, self::COLUMNS[0], (string) $index, 7.5, self::TEXT);

        $nameTop = $top + 7;
        foreach($nameLines as $line) {

            $this->text(self::LEFT + 34, $nameTop, $line, 8.2, self::NAVY, true);
            $nameTop += 10;

        }

        $codeTop = $nameTop + 2;
        foreach($codeLines as $line) {

            $this->text(self::LEFT + 34, $codeTop, $line, 6.8, self::MUTED);
            $codeTop += 8;

        }

        $quantity = (float) $alert->quantity;
        $minimum = (float) $alert->minimum_stock;
        $unit = trim((string) ($alert->unit_abbreviation ?? ""));
        $suffix = $unit === "" ? "" : " ".$unit;
        $values = [
            number_format($quantity, 3, ".", ",").$suffix,
            number_format($minimum, 3, ".", ",").$suffix,
            number_format(max(0, $minimum - $quantity), 3, ".", ",").$suffix,
        ];

        $x = self::LEFT + self::COLUMNS[0] + self::COLUMNS[1];
        foreach($values as $value) {

            $this->rightText($x + 82, $top + 11, $value, 8, self::TEXT);
            $x += 90;

        }

        $detectedAt = $alert->detected_at ? Carbon::parse($alert->detected_at)->format("d/m/Y H:i") : "—";
        $this->centerText($x, $top + 11, self::COLUMNS[5], $detectedAt, 7.5, self::TEXT);
        $this->centerText($x + self::COLUMNS[5], $top + 11, self::COLUMNS[6], "Abierta", 7.5, self::WARNING, true);

    }

    private function drawFooters(string $branch, string $warehouse, Carbon $generatedAt): void {

        $footer = $this->fitText("Blapos · Control de stock · ".$branch." / ".$warehouse, 305, 7, $this->regularFont);
        $this->canvas->page_line(self::LEFT, 558, self::RIGHT, 558, self::BORDER, 0.5);
        $this->canvas->page_text(self::LEFT, 566, $footer, $this->regularFont, 7, self::MUTED);
        $this->canvas->page_text(390, 566, "Página {PAGE_NUM} de {PAGE_COUNT}", $this->regularFont, 7, self::MUTED);
        $this->canvas->page_text(725, 566, "Generado ".$generatedAt->format("d/m/Y H:i"), $this->regularFont, 7, self::MUTED);

    }

    private function text(float $x, float $y, string $text, float $size, array $color, bool $bold = false): void {

        $this->canvas->text($x, $y, $text, $bold ? $this->boldFont : $this->regularFont, $size, $color);

    }

    private function rightText(float $right, float $top, string $text, float $size, array $color, bool $bold = false): void {

        $font = $bold ? $this->boldFont : $this->regularFont;
        $this->text($right - $this->canvas->get_text_width($text, $font, $size), $top, $text, $size, $color, $bold);

    }

    private function centerText(float $left, float $top, float $width, string $text, float $size, array $color, bool $bold = false): void {

        $font = $bold ? $this->boldFont : $this->regularFont;
        $this->text($left + ($width - $this->canvas->get_text_width($text, $font, $size)) / 2, $top, $text, $size, $color, $bold);

    }

    private function fitText(string $text, float $width, float $size, string $font): string {

        while($text !== "" && $this->canvas->get_text_width($text, $font, $size) > $width) {

            $text = mb_substr($text, 0, -2)."…";

        }

        return $text;

    }

    private function wrapText(string $text, float $width, float $size, string $font): array {

        $words = preg_split("/\s+/u", trim($text)) ?: [];
        $lines = [];
        $current = "";

        foreach($words as $word) {

            $candidate = $current === "" ? $word : $current." ".$word;

            if($this->canvas->get_text_width($candidate, $font, $size) <= $width) {

                $current = $candidate;
                continue;

            }

            if($current !== "") {

                $lines[] = $current;
                $current = "";

            }

            foreach(mb_str_split($word) as $character) {

                $candidate = $current.$character;
                if($current !== "" && $this->canvas->get_text_width($candidate, $font, $size) > $width) {

                    $lines[] = $current;
                    $current = "";
                }
                $current .= $character;

            }

        }

        if($current !== "") {

            $lines[] = $current;

        }

        return $lines ?: ["—"];

    }

}
