<?php

namespace Database\Seeders;

use Faker\Factory as WithFaker;
use App\Helpers\StrHelper;
use App\Models\Order;
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
            $orderTypes = rand(0, 1) ?
                [Order::TYPE_SENDING_MONEY, Order::TYPE_RECEIVING_MONEY] :
                [Order::TYPE_REQUESTING_MONEY, Order::TYPE_REQUESTED_MONEY];
            $orderStatus =
                [Order::STATUS_FAILED, Order::STATUS_COMPLETED, Order::STATUS_PENDING, Order::STATUS_REJECTED]; //[rand(0, 3)];
            $randomDateTime = Carbon::now()->subDays(rand(1, 365));

            $order1 = [
                "id" => strtoupper(StrHelper::random(14))  . $randomDateTime->timestamp,
                "user_id" => $transaction->from_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "transaction_id" => $transaction->id,
                "note" => $faker_ID->sentences(1, 2, true),
                "type" => $orderTypes[0],
                "status" => in_array(Order::TYPE_REQUESTING_MONEY, $orderTypes) ? $orderStatus[rand(0, 3)] : $orderStatus[rand(0, 2)],
                "amount" => $transaction->amount,
                "charge" => $transaction->charge,
                "started_at" => $randomDateTime->toDateTimeString(),
                "completed_at" => $randomDateTime->addDays(rand(1, 2))->toDateTimeString(),
                "updated_at" => null,
            ];
            $order2 = array_merge($order1, [
                "id" => strtoupper(StrHelper::random(14))  . $randomDateTime->timestamp,
                "user_id" => $transaction->to_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "type" => $orderTypes[1],
                "charge" => null,
            ]);
            Order::insert([$order1, $order2,]);
        });

        Transaction::factory()->count($total)->create(['from_wallet' => 1])->each(function (Transaction $transaction)  use ($total, $faker_ID) {
            $randomDateTime = Carbon::now()->subDays(rand(1, 365));

            $orderTypeGift = [
                "id" => strtoupper(StrHelper::random(14))  . now()->timestamp,
                "user_id" => $transaction->to_wallet,
                "from_wallet" => $transaction->from_wallet,
                "to_wallet" => $transaction->to_wallet,
                "transaction_id" => $transaction->id,
                "note" => $faker_ID->sentences(1, 2, true),
                "type" => Order::TYPE_GIFT_FROM(),
                "status" => Order::STATUS_COMPLETED,
                "amount" => $transaction->amount,
                "charge" => $transaction->charge,
                "started_at" => $randomDateTime->toDateTimeString(),
                "completed_at" => $randomDateTime->addDay()->toDateTimeString(),
                "updated_at" => null,
            ];
            Order::create($orderTypeGift);
        });
    }
}
