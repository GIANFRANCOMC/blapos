<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de stock</title>
</head>
<body style="margin:0;padding:0;background:#f7f8fa;font-family:Arial,Helvetica,sans-serif;color:#1a1a35;">
    @php
        $warehouseItem = $alert->warehouseItem;
        $item = $warehouseItem?->item;
        $warehouse = $warehouseItem?->warehouse;
        $branch = $warehouse?->branch;
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f8fa;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="height:4px;background:#f59e0b;"></td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 10px;">
                            <p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;color:#2899e5;letter-spacing:.4px;">
                                Inventario
                            </p>
                            <h1 style="margin:0;font-size:20px;line-height:1.3;color:#1a1a35;">
                                {{ $stockStatus }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 24px;font-size:14px;line-height:1.6;color:#334155;">
                            <p style="margin:0 0 12px;">
                                El producto <strong>{{ $item?->name ?? "Sin nombre" }}</strong> requiere atención.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;">
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;color:#64748b;">Sucursal</td>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $branch?->name ?? "Sin sucursal" }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;color:#64748b;">Almacén</td>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $warehouse?->name ?? "Sin almacén" }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;color:#64748b;">Stock actual</td>
                                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $quantity }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;color:#64748b;">Stock mínimo</td>
                                    <td style="padding:8px;text-align:right;">{{ $minimumStock }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px;background:#f7f8fa;color:#64748b;font-size:12px;">
                            Revisa el módulo de Inventario para reponer, corregir o trasladar stock.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
