@php
    $whatsappFilter = $touchpoints->where("type", "whatsapp");
    $whatsapp = count($whatsappFilter) === 1 ? $whatsappFilter->first() : null;
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tu membresía ha expirado</title>
</head>
<body style="font-family: Arial, sans-serif; background: white; padding: 0; margin: 0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 40px 0; background: {{ $ownerApp->color_palette->primary }};">
                <div style="display: inline-block; vertical-align: middle; color: {{ $ownerApp->color_palette->text_by_primary }}; font-size: 18px;">
                    {{ $company->commercial_name }} by
                </div>
                <a href="{{ $ownerApp->web }}" style="display: inline-block; vertical-align: middle; margin-left: 3px;" target="_blank">
                    <img src="{{ asset($ownerApp->assets->img->logotype) }}" alt="Logo" width="100" style="vertical-align: middle; display: inline-block;"/>
                </a>
                <h1 style="color: {{ $ownerApp->color_palette->text_by_primary }}; font-size: 24px; margin: 10px 0 0;">¡Te extrañamos, {{ $customer->name }}!</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; background: #ffffff;">
                <p style="font-size: 16px; color: #333; text-align: center;">
                    Tu membresía expiró el <strong>{{ \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}</strong>. <br>
                    ¡Renueva en las próximas <strong>48 horas</strong> y obtén un <span style="color: #e60000;">10% de descuento</span>!
                </p>

                <div style="text-align: center; margin: 25px 0;">
                    @foreach($touchpoints as $touchpoint)
                        <a href="{{ $touchpoint->link }}" target="_blank" style="background-color: {{ $ownerApp->color_palette->secondary }}; color: white; padding: 14px 25px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold; box-shadow: 0 3px 6px rgba(0,0,0,0.15);">
                            Renovar ahora
                        </a>
                    @endforeach
                </div>

                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

                <p style="font-size: 14px; color: #555;">
                    Información de tu última membresía:
                </p>
                <ul style="color: #333; font-size: 14px; padding-left: 20px;">
                    <li>Sede: <strong>{{ $branch->name }}</strong></li>
                    <li>Duración: <strong>{{ $subscription->formatted_duration }}</strong></li>
                    <li>Inicio: <strong>{{ \Carbon\Carbon::parse($subscription->start_date)->format("d/m/Y H:i") }}</strong></li>
                    <li>Expiración: <strong>{{ \Carbon\Carbon::parse($subscription->end_date)->format("d/m/Y H:i") }}</strong></li>
                </ul>

                <p style="font-size: 14px; color: #555; margin-top: 20px;">
                    Beneficios al renovar:
                </p>
                <ul style="color: #333; font-size: 14px; padding-left: 20px;">
                    <li>Acceso ilimitado a todas las máquinas</li>
                    <li>Clases grupales gratuitas</li>
                    <li>Asesoría personalizada con instructores</li>
                    <li>Descuentos exclusivos en tienda</li>
                </ul>
                @if(!empty($whatsapp?->link))
                    <p style="font-size: 14px; color: #555; margin-top: 20px;">
                        ¿Tienes dudas? <a href="{{ $whatsapp->link }}" target="_blank" style="color: #2899E5; text-decoration: none;">Escríbenos por WhatsApp</a>, responde este correo o visítanos.
                    </p>
                @endif
            </td>
        </tr>
        <tr>
            <td align="center" style="padding: 20px 0; background: {{ $ownerApp->color_palette->primary }};">
                <p style="color: {{ $ownerApp->color_palette->text_by_primary }}; font-size: 12px; margin: 0;">
                    ¡Gracias por elegir {{ $company->commercial_name }}! Esperamos verte pronto.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
