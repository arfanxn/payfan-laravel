<?php

namespace Database\Factories;

use App\Helpers\StrHelper;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $now = now();
        return [
            "id" => strtoupper(StrHelper::random(14))  . $now()->timestamp,
            "note" => $this->withFaker()->sentences(1, 2, true),
            "status" => Payment::STATUS_COMPLETED,
            "amount" => rand(1, 999999) . "." . rand(1, 99),
            "created_at" => $now->toDateTimeString(),
            "completed_at" => $now->toDateTimeString(),
            "updated_at" => $now->toDateTimeString(),

            //fill these attributes with function parameters 
            // "user_id" => $fromWalletData->user_id, 
            // "from_wallet" => $fromWalletData->id,
            // "to_wallet" => $toWalletData->id,
            // "transaction_id" => $transactionID,
            // "type" => Payment::TYPE_MAKE_REQUEST_PAYMENT,
            // end 
        ];
    }
}
