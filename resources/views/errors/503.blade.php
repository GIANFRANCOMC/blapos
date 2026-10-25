@include("errors.page", [
    "code" => 503,
    "heading" => "Servicio temporalmente no disponible",
    "description" => "Estamos realizando tareas de mantenimiento. Vuelve a intentarlo en unos minutos.",
    "actionHref" => url()->current(),
    "actionText" => "Reintentar",
])
