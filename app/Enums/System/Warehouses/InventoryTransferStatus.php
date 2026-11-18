<?php

declare(strict_types=1);

namespace App\Enums\System\Warehouses;

enum InventoryTransferStatus: string {

    case AwaitingDispatch = "awaiting_dispatch";
    case InTransit = "in_transit";
    case PartiallyReceived = "partially_received";
    case Received = "received";
    case ClosedWithDifferences = "closed_with_differences";
    case Canceled = "canceled";

    public function label(): string {
        return match($this) {
            self::AwaitingDispatch => "Por enviar",
            self::InTransit => "En tránsito",
            self::PartiallyReceived => "Recibido parcialmente",
            self::Received => "Recibido",
            self::ClosedWithDifferences => "Cerrado con diferencias",
            self::Canceled => "Cancelado",
        };
    }

    public function tone(): string {
        return match($this) {
            self::AwaitingDispatch, self::PartiallyReceived, self::ClosedWithDifferences => "warning",
            self::InTransit => "info",
            self::Received => "success",
            self::Canceled => "danger",
        };
    }

    public function next(): ?self {
        return $this === self::AwaitingDispatch ? self::InTransit : null;
    }

    public function canCancel(): bool {
        return $this === self::AwaitingDispatch;
    }

    public function canReceive(): bool {
        return $this === self::InTransit || $this === self::PartiallyReceived;
    }

    public function canRegister(): bool {
        return $this === self::AwaitingDispatch || $this === self::InTransit;
    }

}
