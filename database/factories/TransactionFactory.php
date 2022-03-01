<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Helpers\StrHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $started_at  = now()->subDays(rand(1, 30));
        $completed_at = $started_at->addMinutes(rand(1, 30));
        return [
            "tx_hash" =>  StrHelper::make(StrHelper::random())->toUpperCase()->result(),
            "from_wallet" => rand(1, 10), "to_wallet" => rand(11, 20),
            "status" => Transaction::STATUS_COMPLETED,
            "type" => rand(0, 1) ? Transaction::TYPE_SEND_MONEY : Transaction::TYPE_REQUEST_MONEY, "note" => $this->faker->sentence(),
            "amount" => rand(1000000, 9999999),
            "charge" => rand(0, 5000),
            "started_at" =>   $started_at->toDateTimeString(),
            "completed_at" => $completed_at->toDateTimeString(),
        ];
    }
}
