<?php

declare(strict_types=1);

namespace App\Enums\System\Warehouses;

enum InventoryTransferLineStatus: string {
    case AwaitingDispatch = "awaiting_dispatch";
    case AwaitingReceipt = "awaiting_receipt";
    case PartiallyReceived = "partially_received";
    case Received = "received";
    case Resolved = "resolved";
    case Canceled = "canceled";

    public function label(): string {
        return match($this) {
            self::AwaitingDispatch => "Por enviar",
            self::AwaitingReceipt => "Por recibir",
            self::PartiallyReceived => "Recibido parcialmente",
            self::Received => "Recibido",
            self::Resolved => "Resuelto con diferencias",
            self::Canceled => "Cancelado",
        };
    }

    public function tone(): string {
        return match($this) {
            self::AwaitingDispatch, self::PartiallyReceived, self::Resolved => "warning",
            self::AwaitingReceipt => "info",
            self::Received => "success",
            self::Canceled => "danger",
        };
    }
}
