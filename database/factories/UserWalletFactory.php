<?php

namespace Database\Factories;

use App\Helpers\StrHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "address" => StrHelper::make(StrHelper::random(16))->toUpperCase()->get(),
            "balance" => rand(1000000.00, 999999),
            "total_transaction" => rand(0, 100),
            "last_transaction"  => now()->subDays(rand(1, 31))->toDateTimeString()
        ];
    }
}
