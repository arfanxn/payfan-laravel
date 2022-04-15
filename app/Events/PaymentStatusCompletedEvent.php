<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $payment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order|string|int $payment)
    {
        if ($payment instanceof Order)
            $this->payment = $payment;
        else
            $this->payment = Order::query()->where("id", $payment)->first();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('payments.' . $this->payment->id);
    }
}
