<?php

namespace Database\Factories;

use Faker\Factory as WithFaker;
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
        return self::template();
    }

    public static function template(array $custom = []): array
    {
        $faker_ID = WithFaker::create("id_ID");

        return array_merge([
            "id" => $faker_ID->uuid(),
            "type" => \App\Notifications\SendMoneyNotification::class,
            "notifiable_type" => \App\Models\User::class,
            "notifiable_id" => 2,
            "data" => json_encode([
                "text" => $faker_ID->sentences(rand(1, 3), true),
                "action" => rand(0, 1) ? "Transaction Details" : null,
                "link" => $faker_ID->url(),
            ]),
            "read_at" =>  rand(0, 1) ? now()->toDateTimeString() : null,
            "created_at" =>  now()->toDateTimeString(),
        ], $custom);
    }
}
