<?php

namespace App\Events;

use App\Models\Wallet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Wallet $wallet;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Wallet|string|int $wallet)
    {
        if ($wallet instanceof Wallet)
            $this->wallet = $wallet;
        else
            $this->wallet = Wallet::query()->where("id", $wallet)->orWhere("address", $wallet)->first();
    }

    // public function broadcastWith()
    // {
    //     return $this->wallet->makeHidden(['id',])->toArray();
    // }

    // public function broadcastAs()
    // {
    //     return "WalletUpdated";
    // }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('wallets.' . $this->wallet->id);
    }
}
