<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\SendMoneyNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array`
     */
    public function definition()
    {
        return [
            "id" => $this->withFaker()->uuid(),
            "type" => SendMoneyNotification::class,
            "notifiable_type" => User::class,
            "notifiable_id" => rand(1, 5),
            "data" => json_encode([
                "note" => $this->withFaker()->sentence()
            ]),
        ];
    }
}
