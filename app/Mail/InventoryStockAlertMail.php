<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\System\Warehouses\{InventoryStockAlert};
use Illuminate\Bus\{Queueable};
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Mail\{Mailable};
use Illuminate\Queue\{SerializesModels};

final class InventoryStockAlertMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public readonly InventoryStockAlert $alert
    ) {
    }

    public function envelope(): Envelope {

        $itemName = $this->alert->warehouseItem?->item?->name ?? "Producto";

        return new Envelope(
            subject: "Alerta de stock - {$itemName}"
        );

    }

    public function content(): Content {

        return new Content(
            view: "emails.inventory.stock_alert",
            with: [
                "alert" => $this->alert,
            ]
        );

    }

    public function attachments(): array {

        return [];

    }

}
