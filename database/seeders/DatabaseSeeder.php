<?php

namespace Database\Seeders;

use App\Helpers\StrHelper;
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
        ]);

        \App\Models\User::factory(1)->create([
            "name" => ucwords("muhammad arfan"),
            "email" => "arf@gm.com",
            "email_verified_at" => now()->toDateTimeString(),
            "password" => bcrypt("11112222"),
            "profile_pict" => "#" . StrHelper::random(6, "1234567890ABCDEF"),
        ]);

        $totalSeed = 500;

        \App\Models\User::factory()->count($totalSeed)->create();

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
        // $this->call(NotificationSeeder::class, false, ['total' => $totalSeed]);

        \App\Models\Wallet::query()->where("user_id", "<=", 5)->update(['balance' => rand(1000000000000, 9999999999999)]);
    }
}
