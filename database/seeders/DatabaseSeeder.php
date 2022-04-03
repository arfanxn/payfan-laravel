<?php

namespace Database\Seeders;

use App\Models\Contact;
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
        \App\Models\User::factory(1)->create([
            "name" => ucwords(config("app.name") . " " . "offcial"),
            "email" => strtolower(config("app.name") . "@gm.com"),
            "email_verified_at" => now()->toDateTimeString(),
            "profile_pict" => "#003087",
            "password" => bcrypt("11112222")
        ])->each(function (\App\Models\User $user) {
            \App\Models\UserSetting::factory()->count(1)->create(['user_id' => $user->id, "two_factor_auth" => false]);
            \App\Models\Wallet::factory()->count(1)->create(['user_id' => $user->id, "balance" => floatval("9999999999999999.00")]);
        });

        \App\Models\User::factory(1)->create([
            "name" => ucwords("muhammad arfan"),
            "email" => "arf@gm.com",
            "email_verified_at" => now()->toDateTimeString(),
            "password" => bcrypt("11112222")
        ])->each(function (\App\Models\User $user) {
            \App\Models\UserSetting::factory()->count(1)->create(['user_id' => $user->id, "two_factor_auth" => false]);
            \App\Models\Wallet::factory()->count(1)->create(['user_id' => $user->id]);
        });

        $totalSeed = 5000;

        \App\Models\User::factory()->count($totalSeed)->create()->each(function (\App\Models\User $user) use ($faker_ID,) {
            \App\Models\UserSetting::factory()->count(1)->create(['user_id' => $user->id]);
            \App\Models\Wallet::factory()->count(1)->create(['user_id' => $user->id,]);
        });

        for ($i = 1; $i <= $totalSeed; $i++) {
            $status = rand(1, 3);
            switch ($status) {
                case 1:
                    $status = Contact::STATUS_ADDED;
                    break;
                case 2:
                    $status = Contact::STATUS_FAVORITED;
                    break;
                default:
                    $status = Contact::STATUS_BLOCKED;
                    break;
            }
            \App\Models\Contact::create([
                "owner_id" => rand(1, 5),
                "saved_id"  => $i + 1,
                "status" => $status,
                "total_transaction" => rand(1, 99),
                'last_transaction' => now()->subDay(rand(1, 30))->toDateTimeString(),
                'added_at' => now()->subDays(rand(31, 365))->toDateTimeString()
            ]);
        }

        $this->call(TransactionSeeder::class,  false, ["total" => $totalSeed]);
        $this->call(NotificationSeeder::class, false, ['total' => $totalSeed]);
    }
}
