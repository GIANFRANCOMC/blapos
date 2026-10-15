<?php

namespace App\Events;

use App\Models\System\Customers\{Subscription};
use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Foundation\Events\{Dispatchable};
use Illuminate\Queue\{SerializesModels};

class SubscriptionExpired {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    /**
     * Create a new event instance.
     */
    public function __construct(Subscription $subscription) {

        $this->subscription = $subscription;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    /* public function broadcastOn(): array {

        return [
            new PrivateChannel("SubscriptionExpired_".$this->subscription->id),
        ];

    } */

}
