<?php

declare(strict_types=1);

namespace App\Services\System\Organizations;

use App\Models\System\Organizations\{BusinessAuditLog};
use Illuminate\Database\Eloquent\{Model};

final class BusinessAuditService {
    private const HIDDEN_FIELDS = [
        "password",
        "password_confirmation",
        "remember_token",
        "secret",
        "secret_encrypted",
        "secret_hash",
        "access_token",
        "api_token",
        "client_secret",
        "private_key",
        "signature",
        "authorization",
        "fingerprint_template",
    ];

    public static function record(
        string $module,
        string $action,
        string $summary,
        ?Model $record = null,
        array $before = [],
        array $after = [],
        array $context = [],
        ?int $branchId = null,
        ?int $userId = null
    ): BusinessAuditLog {

        $request = app()->bound("request") ? request() : null;
        $actorId = $userId ?? $request?->user()?->getAuthIdentifier();

        return BusinessAuditLog::create([
            "branch_id" => $branchId,
            "user_id" => $actorId ? (int) $actorId : null,
            "module" => $module,
            "action" => $action,
            "auditable_type" => $record ? $record::class : null,
            "auditable_id" => $record?->getKey(),
            "summary" => $summary,
            "before_data" => self::sanitize($before),
            "after_data" => self::sanitize($after),
            "context" => self::sanitize($context),
            "ip_address" => $request?->ip(),
            "user_agent" => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
            "occurred_at" => now(),
        ]);

    }

    public static function recordModelChange(Model $model, string $action): ?BusinessAuditLog {

        $before = $action === "created" ? [] : $model->getOriginal();

        $after = $action === "deleted" ? [] : $model->getAttributes();

        return self::record(
            $model->getTable(),
            $action,
            sprintf("%s #%s: %s", $model->getTable(), (string) $model->getKey(), $action),
            $model,
            $before,
            $after,
            [],
            $model->getAttribute("branch_id") ? (int) $model->getAttribute("branch_id") : null
        );

    }

    private static function sanitize(array $data): array {

        $settingKey = strtolower((string) ($data["key"] ?? ""));

        if(array_key_exists("value", $data)
            && preg_match("/(?:secret|password|token|credential|api[_-]?key|private[_-]?key)/", $settingKey)) {

            $data["value"] = "[REDACTED]";

        }

        foreach($data as $key => $value) {

            if(in_array(strtolower((string) $key), self::HIDDEN_FIELDS, true)) {

                unset($data[$key]);

                continue;

            }

            if(is_array($value)) {

                $data[$key] = self::sanitize($value);

            }

        }

        return $data;

    }
}
