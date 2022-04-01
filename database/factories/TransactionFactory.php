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
        $fromWalletID = rand(2, 6);
        $toWalletID = rand(2, 6);

        // $status = [Transaction::STATUS_PENDING, Transaction::STATUS_COMPLETED, Transaction::STATUS_FAILED, TRANSACTION::STATUS_REJECTED];
        // $type = [Transaction::TYPE_REQUEST_MONEY, Transaction::TYPE_SEND_MONEY, Transaction::TYPE_REWARD];
        $transactionID = strtoupper(StrHelper::random(14)) . now()->timestamp;
        return [
            "id" =>  $transactionID,
            "from_wallet" => $fromWalletID,
            "to_wallet" => $fromWalletID != $toWalletID ? $toWalletID : $toWalletID + 1,
            "status" =>   Transaction::STATUS_COMPLETED,    //$status[rand(0, count($status) - 1)],
            // "type" => $type[rand(0, count($type) - 1)],
            // "note" => $this->faker->sentences(rand(2, 4), true),
            "amount" => floatval(rand(0, 1000000) .  "." . rand(1, 99)),
            "charge" => rand(0, 5000),
            "created_at" =>   now()->toDateTimeString(),
            "updated_at" => null
        ];
    }
}
