<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\{Controller};
use App\Http\Requests\Guest\{BiometricDeviceEventRequest};
use App\Services\System\Devices\BiometricDevices\{BiometricEventService};
use DomainException;

final class BiometricEventController extends Controller {
    public function store(BiometricDeviceEventRequest $request) {

        try {

            $event = BiometricEventService::receive(
                (string) $request->header("X-Device-Key"),
                (string) $request->header("X-Device-Signature"),
                $request->getContent(),
                $request->validated()
            );

            return response()->json([
                "bool" => true,
                "event_uuid" => $event->event_uuid,
                "status" => $event->processing_status,
            ], 202);

        }catch(DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }
}
