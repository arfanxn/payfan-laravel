<?php

namespace Database\Seeders;

use Faker\Factory as WithFaker;
use App\Helpers\StrHelper;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($total = 1000)
    {
        $faker_ID = WithFaker::create("id_ID");

        Transaction::factory()->count($total)->create()->each(function (Transaction $transaction)  use ($total, $faker_ID) {
            $paymentTypes = rand(0, 1) ?
                [Payment::TYPE_SENDING, Payment::TYPE_RECEIVING] :
                [Payment::TYPE_REQUESTING, Payment::TYPE_REQUESTED];
            $paymentStatus =
                [Payment::STATUS_FAILED, Payment::STATUS_COMPLETED, Payment::STATUS_PENDING, Payment::STATUS_REJECTED]; //[rand(0, 3)];
            $randomDateTime = Carbon::now()->subDays(rand(1, 365));

            $payment1 = [
                "id" => strtoupper(StrHelper::random(14))  . $randomDateTime->timestamp,
                "user_id" => $transaction->from_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "transaction_id" => $transaction->id,
                "note" => $faker_ID->sentences(1, 2, true),
                "type" => $paymentTypes[0],
                "status" => in_array(Payment::TYPE_REQUESTING, $paymentTypes) ? $paymentStatus[rand(0, 3)] : $paymentStatus[rand(0, 2)],
                "amount" => $transaction->amount,
                "charge" => $transaction->charge,
                "created_at" => $randomDateTime->toDateTimeString(),
                "completed_at" => $randomDateTime->addDays(rand(1, 2))->toDateTimeString(),
                "updated_at" => $randomDateTime->toDateTimeString(),
            ];
            $payment2 = array_merge($payment1, [
                "id" => strtoupper(StrHelper::random(14))  . $randomDateTime->timestamp,
                "user_id" => $transaction->to_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "type" => $paymentTypes[1],
                "charge" => null,
            ]);
            Payment::insert([$payment1, $payment2,]);
        });

        Transaction::factory()->count($total)->create(['from_wallet' => 1])->each(function (Transaction $transaction)  use ($total, $faker_ID) {
            $randomDateTime = Carbon::now()->subDays(rand(1, 365));

            $paymentTypeGift = [
                "id" => strtoupper(StrHelper::random(14))  . now()->timestamp,
                "user_id" => $transaction->to_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "transaction_id" => $transaction->id,
                "note" => $faker_ID->sentences(1, 2, true),
                "type" => Payment::TYPE_GIFT_FROM(),
                "status" => Payment::STATUS_COMPLETED,
                "amount" => $transaction->amount,
                "charge" => $transaction->charge,
                "created_at" => $randomDateTime->toDateTimeString(),
                "completed_at" => $randomDateTime->addDay()->toDateTimeString(),
                "updated_at" => $randomDateTime->toDateTimeString(),
            ];
            Payment::create($paymentTypeGift);
        });

        Payment::where("status", Payment::STATUS_PENDING)->update([
            "completed_at" => null,
        ]);
    }
}
