# 01 - Inicio Público

## Qué Hace

Muestra información pública de la empresa, categorías activas y catálogo comercial disponible para clientes.

## Archivos

- Ruta: `routes/Guest/Home.php`
- Controlador: `Guest/HomeController`
- Servicio: `app/Services/Guest/GuestCatalogService.php`
- Modelos públicos: `App\Models\Guest\Company`, `Item` y `Category`
- Vista/Vue: `resources/views/Guest/general/home`, `resources/js/Guest/Pages/home`
- Tablas: `companies`, `company_socials_media`, `items`, `categories`, `category_items`, `currencies`

## Reglas

- La empresa debe estar activa y se resuelve por slug dentro del tenant actual.
- Solo se consultan items activos con `see_my_web = true`.
- El precio se expone únicamente cuando `see_my_web_price = true`.
- Si el precio está oculto, el backend no entrega `price`, `min_price`, `max_price` ni `currency`.
- Las categorías públicas deben estar activas y se ordenan por nombre; no existe un interruptor de publicación propio de Categoría.
- El contrato excluye tokens externos, auditoría y columnas internas que el visitante no necesita.

## UI/UX Implementado

- El layout público genera metadatos SEO, Open Graph y Twitter Card por empresa.
- El catálogo es responsive y permite filtrar por categorías públicas.
- Cada item publicado permite iniciar consulta por WhatsApp.
- La vista muestra **Precio a consultar** cuando la empresa decide ocultar importes.
- Los textos públicos usan lenguaje directo para visitantes y no mencionan configuraciones internas.
- El número de WhatsApp se normaliza en frontend para evitar enlaces rotos por espacios, guiones o prefijos escritos con `+`.
- Los controles públicos tienen foco visible para navegación por teclado.
