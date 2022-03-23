<?php

namespace Database\Seeders;

use Faker\Factory as WithFaker;
use App\Helpers\StrHelper;
use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

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
        $now = Carbon::now();

        Transaction::factory()->count($total)->create()->each(function (Transaction $transaction)  use ($total, $now, $faker_ID) {
            $ordertypes = rand(0, 1) ?
                [Order::TYPE_SENDING_MONEY, Order::TYPE_RECEIVING_MONEY] :
                [Order::TYPE_REQUESTING_MONEY, Order::TYPE_REQUESTED_MONEY];

            $order1 = [
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $transaction->from_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "transaction_id" => $transaction->id,
                "note" => $faker_ID->sentences(1, 2, true),
                "type" => $ordertypes[0],
                "status" => Order::STATUS_COMPLETED,
                "amount" => $transaction->amount,
                "charge" => $transaction->charge,
                "started_at" => $now->toDateTimeString(),
                "completed_at" => $now->toDateTimeString(),
                "updated_at" => null,
            ];
            $order2 = array_merge($order1, [
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $transaction->to_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "type" => $ordertypes[1],
                "charge" => null,
            ]);
            Order::insert([$order1, $order2]);
        });
    }
}
