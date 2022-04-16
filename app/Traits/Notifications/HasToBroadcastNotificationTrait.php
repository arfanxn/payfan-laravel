<?php

namespace App\Traits\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;

trait HasToBroadcastNotificationTrait
{
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $data = [];
        if (method_exists(self::class, "toBroadcastData"))
            $data = $this->toBroadcastData($notifiable);
        else if (method_exists(self::class, "toBroadcastCustomData"))
            $data = $this->toBroadcastCustomData($notifiable);
        else if (method_exists(self::class, "toArray"))
            $data = $this->toArray($notifiable);
        else if (method_exists(self::class, "toDatabase"))
            $data =  $this->toDatabase($notifiable);

        return (new BroadcastMessage([
            "id" => $this->id,
            "type" => __CLASS__,
            "notifiable_id" => $notifiable->id,
            "notifiable_type" => get_class($notifiable),
            "data" =>  $data,
            "created_at" => now()->toIsoString(),
            "updated_at" => now()->toIsoString(),
            "read_at" => null,
        ])/**/);
    }
}
