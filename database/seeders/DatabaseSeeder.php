<?php

namespace Database\Seeders;

use Faker\Factory as WithFaker;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker_ID = WithFaker::create("id_ID");
        \App\Models\User::create([
            "name" => ucwords("muhammad arfan"),
            "email" => "arf@gm.com",
            "email_verified_at" => now()->toDateTimeString(),
            "password" => bcrypt("11112222")
        ]);

        $totalSeed = 2000;
        \App\Models\User::factory($totalSeed)->create();
        \App\Models\Notification::factory($totalSeed)->create();

        for ($i = 1; $i <= $totalSeed; $i++) {
            \App\Models\UserSetting::create([
                "user_id" => $i,
                "two_factor_auth" => rand(0, 1),
                "security_question" => substr(strtolower($faker_ID->sentence()), 0, 50),
                "security_answer" => substr(strtolower($faker_ID->sentence()), 0, 50)
            ]);

            \App\Models\Contact::create([
                "owner_id" => rand(1, 5),
                "saved_id"  => $i + 1,
                "status" => \App\Models\Contact::STATUS_ADDED,
                "total_transaction" => rand(1, 99),
                'last_transaction' => now()->subDay(rand(1, 30))->toDateTimeString(),
                'added_at' => now()->subDays(rand(31, 365))->toDateTimeString()
            ]);
        }
    }
}
