<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslado {{ $transfer->reference }}</title>
    <style>
        @page { margin: 34pt 38pt; }
        body { color: #1a1a35; font: 10pt 'DejaVu Sans', sans-serif; line-height: 1.45; }
        h1 { font-size: 18pt; margin: 14pt 0 4pt; }
        h2 { font-size: 11pt; margin: 15pt 0 6pt; }
        p { margin: 0 0 4pt; }
        .meta { color: #535d70; }
        .summary, .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .summary { margin: 14pt 0 10pt; }
        .summary td { border: 1px solid #dbe2ee; padding: 8pt; vertical-align: top; }
        .items thead { display: table-header-group; }
        .items th { color: #fff; background: #374d73; text-align: left; }
        .items th, .items td { padding: 6pt 8pt; overflow-wrap: break-word; }
        .items td { border-bottom: 1px solid #dbe2ee; vertical-align: middle; }
        .items tr { page-break-inside: avoid; }
        .items .amount { text-align: right; width: 18%; white-space: nowrap; }
        .codes { font-size: 9pt; color: #535d70; }
        .reason { white-space: pre-wrap; overflow-wrap: break-word; }
    </style>
</head>
<body>
    @php($numbers = \App\Helpers\System\Numbers::class)
    @if($company?->commercial_name || $company?->legal_name)
        <p>{{ $company->commercial_name ?: $company->legal_name }}</p>
    @endif
    @if($company?->document_number)
        <p class="meta">Documento de la empresa {{ $company->document_number }}</p>
    @endif

    <h1>Traslado de inventario</h1>
    <p>{{ $transfer->reference }}</p>
    <p class="meta">{{ $transfer->status->label() }} · {{ $transfer->created_at->format('d/m/Y H:i') }} · {{ $transfer->user?->name ?? 'Proceso del sistema' }}</p>

    <table class="summary">
        <tr>
            @foreach(['Almacén de origen' => $transfer->sourceWarehouse, 'Almacén de destino' => $transfer->destinationWarehouse] as $label => $warehouse)
                <td>
                    <p class="meta">{{ $label }}</p>
                    <p>{{ $warehouse?->name ?? 'Almacén no visible' }}</p>
                    @if($warehouse?->branch)
                        <p class="meta">{{ $warehouse->branch->name }}</p>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>

    <p class="meta">Motivo del traslado</p>
    <p class="reason">{{ $transfer->reason }}</p>

    @if($transfer->canceled_at)
        <h2>Cancelación del traslado</h2>
        <p class="meta">{{ $transfer->canceled_at->format('d/m/Y H:i') }} · {{ $transfer->canceledByUser?->name ?? 'Usuario no disponible' }}</p>
        @if($transfer->cancellation_reason)
            <p class="reason">{{ $transfer->cancellation_reason }}</p>
        @endif
    @endif

    <h2>Productos del traslado ({{ $transfer->items_count }})</h2>
    <table class="items">
        <thead>
            <tr><th scope="col">Producto</th><th scope="col" class="amount">Cantidad</th></tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $line)
                <tr>
                    <td>
                        {{ $line->item->name }}
                        @if($line->item->internal_code || $line->item->barcode)
                            <div class="codes">
                                @if($line->item->internal_code) Cód. interno: {{ $line->item->internal_code }} @endif
                                @if($line->item->internal_code && $line->item->barcode) · @endif
                                @if($line->item->barcode) Cód. barras: {{ $line->item->barcode }} @endif
                            </div>
                        @endif
                    </td>
                    <td class="amount">{{ $numbers::formatDecimal($line->quantity) }} {{ $line->item->measurementUnit?->abbreviation }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
