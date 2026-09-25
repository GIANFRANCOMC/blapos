<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Dispositivos Biométricos
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Dispositivos Biométricos. Estas pueden
    | personalizarse para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Dispositivo biométrico creado correctamente.",
    "updated" => "Dispositivo biométrico editado correctamente.",
    "deleted" => "Dispositivo biométrico eliminado correctamente.",
    "retrieved" => "Dispositivo biométrico obtenido correctamente.",

    // Mensajes de Error
    "not_found" => "Dispositivo biométrico no encontrado.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se ha podido crear el dispositivo biométrico.",
    "update_failed" => "No se ha podido editar el dispositivo biométrico.",
    "delete_failed" => "No se ha podido eliminar el dispositivo biométrico.",
    "retrieve_failed" => "No se ha podido obtener el dispositivo biométrico.",
    "delete_not_implemented" => "Funcionalidad de eliminación no implementada.",

    // Mensajes de Validación

    // Mensajes Generales
    "init_params_error" => "Error al obtener parámetros de inicialización.",
    "list_error" => "Error al obtener la lista de dispositivos biométricos.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear el dispositivo biométrico: :message",
    "exception_update" => "Error al editar el dispositivo biométrico: :message",
    "exception_delete" => "Error al eliminar el dispositivo biométrico: :message",
    "exception_retrieve" => "Error al obtener el dispositivo biométrico: :message",
    "exception_list" => "Error al obtener la lista de dispositivos biométricos: :message",

    // Mensajes de Eventos Biométricos
    "company_not_identified" => "No se pudo identificar la empresa.",
    "user_id_required" => "El parámetro 'user_id' es requerido.",
    "action_invalid" => "El parámetro 'action' debe ser 'checkin' o 'checkout'.",
    "device_not_found_or_unauthorized" => "Dispositivo biométrico no encontrado o no autorizado. Verifique la configuración del dispositivo.",
    "user_not_found" => "Usuario no encontrado en el sistema. Verifique que la huella esté registrada correctamente.",
    "event_processing_error" => "Error al procesar el evento biométrico.",
    "biometric_record_observation" => "Registro biométrico - :device_name",
];
