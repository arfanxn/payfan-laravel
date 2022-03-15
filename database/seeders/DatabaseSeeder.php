<?php

namespace Database\Seeders;

use App\Helpers\StrHelper;
use App\Models\Contact;
use Faker\Factory as WithFaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        \App\Models\UserSetting::factory()->count(1)->create(['user_id' => 1]);
        \App\Models\UserWallet::factory()->count(1)->create(['user_id' => 1]);

        $totalSeed = 2000;

        \App\Models\User::factory()->count($totalSeed)->create()->each(function (\App\Models\User $user) use ($faker_ID,) {
            \App\Models\UserSetting::factory()->count(1)->create(['user_id' => $user->id]);
            \App\Models\UserWallet::factory()->count(1)->create(['user_id' => $user->id,]);
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

        \App\Models\Transaction::factory($totalSeed)->create();

        $this->call(NotificationSeeder::class, false, ['total' => $totalSeed]);
    }
}
