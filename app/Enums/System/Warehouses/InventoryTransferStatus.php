<?php

declare(strict_types=1);

namespace App\Enums\System\Warehouses;

enum InventoryTransferStatus: string {

    case Pending = "pending";
    case Sent = "sent";
    case Accepted = "accepted";
    case Canceled = "canceled";

    public function label(): string {
        return match($this) {
            self::Pending => "Pendiente",
            self::Sent => "Enviado",
            self::Accepted => "Aceptado",
            self::Canceled => "Cancelado",
        };
    }

    public function tone(): string {
        return match($this) {
            self::Pending => "warning",
            self::Sent => "info",
            self::Accepted => "success",
            self::Canceled => "danger",
        };
    }

    public function next(): ?self {
        return match($this) {
            self::Pending => self::Sent,
            self::Sent => self::Accepted,
            self::Accepted, self::Canceled => null,
        };
    }

    public function canCancel(): bool {
        return $this === self::Pending || $this === self::Sent;
    }

}
