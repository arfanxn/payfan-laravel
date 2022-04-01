<?php

namespace Database\Seeders;

use Database\Factories\NotificationFactory;
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
            DB::table("notifications")->insert(NotificationFactory::template(['notifiable_id' => 2]));
        }
    }
}
