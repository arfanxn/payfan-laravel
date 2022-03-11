<?php

namespace Database\Seeders;

use Faker\Factory as WithFaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($total = 1000)
    {
        $faker_ID = WithFaker::create("id_ID");

        for ($i = 1; $i <= $total; $i++) {
            DB::table("notifications")->insert([
                "id" => $faker_ID->uuid(),
                "type" => \App\Notifications\SendMoneyNotification::class,
                "notifiable_type" => \App\Models\User::class,
                "notifiable_id" => 1,
                "data" => json_encode([
                    "text" => $faker_ID->sentence(),
                    "action" => rand(0, 1) ? "Transaction Detail" : null,
                    "link" => $faker_ID->url(),
                ]),
            ]);
        }
    }
}
