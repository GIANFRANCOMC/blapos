<?php

declare(strict_types=1);

namespace App\Helpers\System;

use Illuminate\Support\Facades\{App};

/**
 * Translation Helper
 * Provides centralized translation management with entity-based organization
 */
class TranslationHelper {

    /**
     * Get translation for a specific entity and key
     *
     * @param  string  $entity Entity name (e.g., 'System.Organizations.branch', 'branch')
     * @param  string  $key Translation key
     * @param  array  $replace Replacements for placeholders
     * @param  string|null  $locale Locale override (defaults to current locale)
     */
    public static function get(string $entity, string $key, array $replace = [], ?string $locale = null): string {

        $locale = $locale ?? App::getLocale();

        // Convert dots to slashes for nested directories
        $filePath = str_replace(".", "/", $entity);

        $translationKey = "{$filePath}.{$key}";

        return trans($translationKey, $replace, $locale);

    }

    /**
     * Get translation with fallback to English if not found, and finally to default messages
     *
     * @param  string  $entity Entity name (e.g., 'System.Organizations.branch')
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     * @param  string|null  $locale Locale override
     */
    public static function getWithFallback(string $entity, string $key, array $replace = [], ?string $locale = null): string {

        $locale = $locale ?? App::getLocale();

        // Convert dots to slashes for nested directories
        $filePath = str_replace(".", "/", $entity);

        $translationKey = "{$filePath}.{$key}";

        // Try current locale first
        $translation = trans($translationKey, $replace, $locale);

        // Check if translation was found (Laravel returns the key if not found)
        // Also check if it contains slashes (which means it's a path, not a translation)

        $isTranslationFound = ($translation !== $translationKey) && (strpos($translation, "/") === false);

        // If translation not found, try English fallback

        if(!$isTranslationFound && $locale !== "en") {

            $translation = trans($translationKey, $replace, "en");
            $isTranslationFound = ($translation !== $translationKey) && (strpos($translation, "/") === false);

        }

        // If still not found, use default messages

        if(!$isTranslationFound) {

            $translation = self::getDefaultMessage($entity, $key, $replace);

        }

        return $translation;

    }

    /**
     * Get default message when translation is not found
     *
     * @param  string  $entity Entity name
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function getDefaultMessage(string $entity, string $key, array $replace = []): string {

        // Extract entity name from namespace (last part)
        $entityParts = explode(".", $entity);

        $entityName = end($entityParts);

        // Map entity names to Spanish labels
        $entityLabels = [
            "user" => "usuario",
            "customer" => "cliente",
            "category" => "categoría",
            "product" => "producto",
            "service" => "servicio",
            "subscription" => "suscripción",
            "company" => "empresa",
            "branch" => "sucursal",
            "book_complaint" => "queja",
            "asset" => "activo",
            "asset_management" => "asignación",
            "sale" => "venta",
            "tracking_attendance" => "asistencia",
            "tracking_subscription" => "suscripción",
            "tracking_customer" => "cliente",
            "tracking_notification" => "notificación",
            "stock_management" => "stock",
            "dashboard" => "tablero",
            "report" => "reporte",
            "home" => "preferencias",
            "helper" => "ayuda",
        ];

        $entityLabel = $entityLabels[$entityName] ?? $entityName;

        // Default messages in Spanish
        $defaultMessages = [
            "created" => ucfirst($entityLabel)." creado exitosamente",
            "updated" => ucfirst($entityLabel)." actualizado exitosamente",
            "deleted" => ucfirst($entityLabel)." eliminado exitosamente",
            "canceled" => ucfirst($entityLabel)." cancelado exitosamente",
            "not_found" => ucfirst($entityLabel)." no encontrado",
            "create_failed" => "No se pudo crear ".$entityLabel,
            "update_failed" => "No se pudo actualizar ".$entityLabel,
            "delete_failed" => "No se pudo eliminar ".$entityLabel,
            "cancel_failed" => "No se pudo cancelar ".$entityLabel,
            "unauthorized" => "No autorizado para realizar esta acción",
            "exception_create" => "Error al crear ".$entityLabel,
            "exception_update" => "Error al actualizar ".$entityLabel,
            "exception_delete" => "Error al eliminar ".$entityLabel,
            "exception_cancel" => "Error al cancelar ".$entityLabel,
        ];

        // Get message or use key as fallback

        $message = $defaultMessages[$key] ?? ucfirst(str_replace("_", " ", $key));

        // Apply replacements if any

        if(!empty($replace)) {

            foreach($replace as $search => $value) {

                $message = str_replace(":".$search, $value, $message);
                $message = str_replace("{".$search."}", $value, $message);

            }

        }

        return $message;

    }

    /**
     * Get all translations for an entity
     *
     * @param  string  $entity Entity name (e.g., 'System.Organizations.branch')
     * @param  string|null  $locale Locale override
     */
    public static function getAll(string $entity, ?string $locale = null): array {

        $locale = $locale ?? App::getLocale();

        // Convert dots to slashes for nested directories
        $filePath = str_replace(".", "/", $entity);

        return trans("{$filePath}", [], $locale);

    }

    /**
     * Check if translation exists
     *
     * @param  string  $entity Entity name (e.g., 'System.Organizations.branch')
     * @param  string  $key Translation key
     * @param  string|null  $locale Locale override
     */
    public static function exists(string $entity, string $key, ?string $locale = null): bool {

        $locale = $locale ?? App::getLocale();

        // Convert dots to slashes for nested directories
        $filePath = str_replace(".", "/", $entity);

        $translationKey = "{$filePath}.{$key}";
        $translation = trans($translationKey, [], $locale);

        return $translation !== $translationKey;

    }

}
