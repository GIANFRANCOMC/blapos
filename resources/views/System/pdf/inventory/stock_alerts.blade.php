<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Alertas de stock</title>
    <style>
        @page { margin: 22mm 15mm 18mm; }
        body { margin: 0; color: #182342; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .topline { height: 5px; margin: -22mm -15mm 18px; background: #1684d5; }
        .heading { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .heading td { vertical-align: top; }
        .brand { color: #0b63a9; font-size: 11px; font-weight: bold; letter-spacing: .08em; }
        .company { margin-top: 4px; color: #182342; font-size: 9px; font-weight: bold; }
        .company-meta { margin-top: 3px; color: #667895; font-size: 8px; }
        .report-title { margin: 0; color: #162448; font-size: 20px; line-height: 1.2; text-align: right; }
        .report-subtitle { margin-top: 5px; color: #667895; text-align: right; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 18px; }
        .summary td { padding: 10px 12px; border: 1px solid #dce7f1; background: #f2f7fb; vertical-align: top; }
        .summary-label { display: block; margin-bottom: 4px; color: #5b6d8b; font-size: 7px; font-weight: bold; letter-spacing: .06em; text-transform: uppercase; }
        .summary-value { display: block; color: #172b50; font-size: 10px; font-weight: bold; }
        .summary-detail { display: block; margin-top: 3px; color: #5b6d8b; font-size: 8px; }
        .summary-count { color: #0b63a9; font-size: 16px; }
        table.alerts { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.alerts thead { display: table-header-group; }
        table.alerts th { padding: 9px 8px; background: #3a507a; color: #fff; font-size: 8px; font-weight: bold; text-align: center; text-transform: uppercase; }
        table.alerts td { padding: 9px 8px; border-bottom: 1px solid #e5ecf3; vertical-align: top; }
        table.alerts tbody tr:nth-child(even) { background: #f7f9fc; }
        table.alerts tbody tr { page-break-inside: avoid; }
        .product-name { color: #142b50; font-weight: bold; line-height: 1.35; }
        .product-code { display: block; margin-top: 3px; color: #5f718e; font-size: 7px; }
        .number { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .status { color: #a95909; font-weight: bold; }
        .empty { padding: 22px 10px !important; color: #667895; text-align: center; }
        .footer { position: fixed; right: 0; bottom: -10mm; left: 0; padding-top: 7px; border-top: 1px solid #dce7f1; color: #6c7e99; font-size: 7px; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <div class="topline"></div>
    <table class="heading">
        <tr>
            <td style="width: 48%;">
                <div class="brand">BLAPOS</div>
                <div class="company">{{ $company?->legal_name ?: ($company?->commercial_name ?: 'Control de inventario') }}</div>
                @if($company?->document_number)
                    <div class="company-meta">RUC {{ $company->document_number }}</div>
                @endif
            </td>
            <td style="width: 52%;">
                <h1 class="report-title">Alertas de stock</h1>
                <div class="report-subtitle">Productos en mínimo o sin existencias · {{ $generatedAt->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td style="width: 31%;">
                <span class="summary-label">Sede</span>
                <span class="summary-value">{{ $warehouse->branch?->name ?: 'Sin sede asignada' }}</span>
            </td>
            <td style="width: 39%;">
                <span class="summary-label">Almacén</span>
                <span class="summary-value">{{ $warehouse->name }}</span>
            </td>
            <td style="width: 30%;">
                <span class="summary-label">Alertas abiertas</span>
                <span class="summary-value summary-count">{{ number_format($alerts->count(), 0, '.', ',') }}</span>
                <span class="summary-detail">Total del almacén seleccionado</span>
            </td>
        </tr>
    </table>

    <table class="alerts">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 34%;">Producto e identificación</th>
                <th style="width: 13%;">Stock actual</th>
                <th style="width: 13%;">Stock mínimo</th>
                <th style="width: 13%;">Faltante</th>
                <th style="width: 15%;">Detectada</th>
                <th style="width: 8%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alerts as $alert)
                @php
                    $item = $alert->warehouseItem?->item;
                    $unit = $item?->measurementUnit?->abbreviation;
                    $quantity = (float) $alert->quantity;
                    $minimum = (float) $alert->minimum_stock;
                    $formatQuantity = static fn(float $value) => number_format($value, 3, '.', ',').($unit ? ' '.$unit : '');
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="product-name">{{ $item?->name ?: 'Producto sin nombre' }}</div>
                        <span class="product-code">Cód. interno: {{ $item?->internal_code ?: '—' }}</span>
                        <span class="product-code">Cód. barras: {{ $item?->barcode ?: '—' }}</span>
                    </td>
                    <td class="number">{{ $formatQuantity($quantity) }}</td>
                    <td class="number">{{ $formatQuantity($minimum) }}</td>
                    <td class="number">{{ $formatQuantity(max(0, $minimum - $quantity)) }}</td>
                    <td class="center">{{ $alert->detected_at?->format('d/m/Y H:i') ?: '—' }}</td>
                    <td class="center status">Abierta</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No hay alertas abiertas en este almacén.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Blapos · Control de stock · {{ $warehouse->branch?->name ?: 'Sin sede asignada' }} / {{ $warehouse->name }}
        <span class="footer-right">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</span>
    </div>
</body>
</html>
