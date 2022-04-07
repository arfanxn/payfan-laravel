<?php

namespace App\Traits\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;

trait HasToBroadcastNotificationTrait
{
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            "id" => $this->id,
            "type" => __CLASS__,
            "notifiable_id" => $notifiable->id,
            "notifiable_type" => get_class($notifiable),
            "data" => $this->toBroadcastData($notifiable) ?? $this->toArray($notifiable) ?? $this->toDatabase($notifiable),
            "created_at" => now()->toIsoString(),
            "updated_at" => null,
            "read_at" => null,
        ])/**/);
    }
}
