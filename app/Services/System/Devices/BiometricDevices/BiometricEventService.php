<?php

declare(strict_types=1);

namespace App\Services\System\Devices\BiometricDevices;

use App\Models\System\Devices\{BiometricDevice, BiometricDeviceEvent};
use App\Services\System\Customers\Tracking\{TrackingAttendanceBusinessService};
use App\Services\System\Organizations\Users\{UserAttendanceService};
use DomainException;
use Illuminate\Support\Facades\{Crypt, DB};
use Throwable;

final class BiometricEventService {
    public static function receive(
        int $companyId,
        string $accessKey,
        string $signature,
        string $rawPayload,
        array $payload
    ): BiometricDeviceEvent {

        $device = BiometricDevice::query()
            ->where("access_key", $accessKey)
            ->where("status", "active")
            ->first();

        if(!$device || !$device->secret_encrypted) {

            throw new DomainException("Las credenciales del dispositivo no son válidas.");

        }

        $expected = hash_hmac("sha256", $rawPayload, Crypt::decryptString($device->secret_encrypted));

        if(!$signature || !hash_equals($expected, $signature)) {

            throw new DomainException("La firma del evento biométrico no es válida.");

        }

        return DB::transaction(function() use ($device, $payload) {

            $event = BiometricDeviceEvent::query()->firstOrCreate(
                [
                    "biometric_device_id" => $device->id,
                    "event_uuid" => $payload["event_uuid"],
                ],
                [
                    "event_type" => $payload["event_type"],
                    "subject_type" => $payload["subject_type"],
                    "device_user_id" => $payload["device_user_id"],
                    "occurred_at" => $payload["occurred_at"],
                    "payload" => $payload["payload"] ?? null,
                    "processing_status" => "pending",
                    "attempts" => 0,
                ]
            );

            if($event->processing_status === "processed") {

                return $event;

            }

            if((int) $event->attempts >= 3) {

                throw new DomainException("El evento agotó sus intentos de procesamiento y requiere revisión.");

            }

            $event->increment("attempts");

            try {

                self::process($device, $event);
                $event->forceFill([
                    "processing_status" => "processed",
                    "processed_at" => now(),
                    "last_error" => null,
                ])->save();

                $device->forceFill(["last_seen_at" => now()])->save();

            }catch(Throwable $exception) {

                $event->forceFill([
                    "processing_status" => "failed",
                    "last_error" => mb_substr($exception->getMessage(), 0, 500),
                ])->save();

                throw $exception;

            }

            return $event->fresh();

        });

    }

    private static function process(BiometricDevice $device, BiometricDeviceEvent $event): void {

        $isCheckout = $event->event_type === "check_out";

        if($event->subject_type === "customer") {

            $result = app(TrackingAttendanceBusinessService::class)->validateAndCreateAttendance([
                "branch_id" => $device->branch_id,
                "device_id" => $device->id,
                "device_user_id" => $event->device_user_id,
                "source_reference" => "event:{$event->event_uuid}",
                "start_date" => $isCheckout ? null : $event->occurred_at,
                "end_date" => $isCheckout ? $event->occurred_at : null,
                "type" => "biometric",
                "action" => $isCheckout ? "checkout" : "automatic",
                "observation" => "Evento biométrico {$event->event_uuid}",
                "user_id" => null,
            ]);

            if(!($result["bool"] ?? false)) {

                throw new DomainException((string) ($result["msg"] ?? "No se procesó la asistencia del cliente."));

            }

            return;

        }

        $user = BiometricDeviceService::findUserByDeviceUserId(
            (int) $device->id,
            (int) $event->device_user_id
        );

        if(!$user) {

            throw new DomainException("No existe un colaborador vinculado con la identidad biométrica.");

        }

        $data = [
            "branch_id" => $device->branch_id,
            "user_id" => $user->id,
            "actor_id" => null,
            "source_type" => UserAttendanceService::SOURCE_BIOMETRIC,
            "source_reference" => "event:{$event->event_uuid}",
        ];

        if($isCheckout) {

            UserAttendanceService::checkOut([...$data, "checked_out_at" => $event->occurred_at]);

        }else {

            UserAttendanceService::checkIn([...$data, "checked_in_at" => $event->occurred_at]);

        }

    }
}
