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
        .items .amount { text-align: right; white-space: nowrap; }
        .product { width: 32%; }
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
    <p class="meta">Estado: {{ $transfer->status->label() }}</p>
    <p class="meta">Fecha de registro: {{ $transfer->created_at->format('d/m/Y H:i') }}</p>
    <p>Registrado por: {{ $transfer->user?->name ?? 'Usuario no disponible' }}</p>
    <p>Enviado por: {{ $transfer->sender_name ?? 'Sin envío confirmado' }}</p>
    <p>Recepciones registradas por: {{ $transfer->receiver_name ?? 'Sin recepción registrada' }}</p>

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

    <h2>Recorrido del traslado</h2>
    @if($transfer->statusHistory->isNotEmpty())
        <table class="items">
            <thead><tr><th>Estado</th><th>Fecha del cambio</th><th>Responsable del cambio</th></tr></thead>
            <tbody>
                @foreach($transfer->statusHistory as $event)
                    <tr>
                        <td>{{ $event->label }}</td>
                        <td>{{ $event->occurred_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $event->user_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="meta">No hay cambios de estado registrados.</p>
    @endif

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
            <tr><th scope="col" class="product">Producto</th><th scope="col" class="amount">{{ in_array($transfer->status->value, ['awaiting_dispatch', 'canceled'], true) ? 'Por enviar' : 'Enviada' }}</th><th class="amount">Recibida</th><th class="amount">Pendiente</th><th class="amount">Resuelta</th><th>Estado</th></tr>
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
                    <td class="amount">{{ $numbers::formatDecimal(in_array($transfer->status->value, ['awaiting_dispatch', 'canceled'], true) ? $line->quantity : $line->sent_quantity) }}</td>
                    <td class="amount">{{ $numbers::formatDecimal($line->received_quantity) }}</td>
                    <td class="amount">{{ $numbers::formatDecimal($line->pending_quantity) }}</td>
                    <td class="amount">{{ $numbers::formatDecimal($line->resolved_quantity) }}</td>
                    <td>{{ $line->status->label() }}<div class="codes">{{ $line->item->measurementUnit?->abbreviation }}</div></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h2>Recepciones y diferencias registradas</h2>
    @forelse($transfer->operations as $operation)
        <p>{{ $operation->label }} #{{ $operation->id }} · {{ $operation->occurred_at->format('d/m/Y H:i') }} · {{ $operation->user_name }}</p>
        @if($operation->reason)<p class="reason meta">{{ $operation->reason }}</p>@endif
        <table class="items"><thead><tr><th>Producto</th><th class="amount">Cantidad</th><th class="amount">Valor</th></tr></thead><tbody>
            @foreach($operation->items as $line)
                <tr><td>{{ $line->transferItem->item->name }}</td><td class="amount">{{ $numbers::formatDecimal($line->quantity) }} {{ $line->transferItem->item->measurementUnit?->abbreviation }}</td><td class="amount">{{ $numbers::formatDecimal($line->value) }}</td></tr>
            @endforeach
        </tbody></table>
    @empty
        <p class="meta">Sin recepciones ni diferencias registradas.</p>
    @endforelse
</body>
</html>
