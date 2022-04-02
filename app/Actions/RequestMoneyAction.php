<?php

namespace App\Actions;

use App\Exceptions\TransactionException;
use App\Helpers\StrHelper;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;

class RequestMoneyAction extends TransactionActionAbstract
{
    public function make()
    {
        try {
            DB::beginTransaction();
            $amount = ($this->amount);
            $note = $this->note ?? "";
            $charge = $this->charge ?? 0;
            $toWallet = $this->toWallet;
            $fromWallet = $this->fromWallet;

            // if the user requesting to the same wallet, let say wallet-id-1 requeesting to wallet-id-1 ,throw an error.
            if ($fromWallet ==  $toWallet)
                throw new TransactionException("Can't request to the same Wallet!");
            // end

            // minumum request must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) throw new TransactionException("Minimum Transaction is $0.10");
            // end

            $fromWalletData = Wallet::where("address", $fromWallet)->first();

            $toWalletData = Wallet::where("address", $toWallet)->first();

            $now = now();
            $transactionID = strtoupper(StrHelper::random(14)) . $now->timestamp;
            Transaction::create([
                "id" => $transactionID,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "amount" => $amount,
                "charge" => $charge,
                "status" => Transaction::STATUS_PENDING,
                "created_at" => $now->toDateTimeString(),
            ]);
            $requesterOrder = [ // create a new order for requester money/payment account
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $fromWalletData->user_id,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Order::TYPE_REQUESTING_MONEY,
                "status" => Order::STATUS_PENDING,
                "charge" => $charge,
                "amount" => $amount,
                "started_at" => $now->toDateTimeString(),
                "completed_at" => null,
                "updated_at" => null,
            ];

            Order::insert([
                $requesterOrder,
                [   // create a new order for  account that being requested money
                    "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                    "user_id" => $toWalletData->user_id,
                    "from_wallet" => $fromWalletData->id,
                    "to_wallet" => $toWalletData->id,
                    "transaction_id" => $transactionID,
                    "note" => $note,
                    "type" => Order::TYPE_REQUESTED_MONEY,
                    "status" => Order::STATUS_PENDING,
                    "amount" => $amount,
                    "charge" => $charge,
                    "started_at" => $now->toDateTimeString(),
                    "completed_at" => null,
                    "updated_at" => null,
                ]
            ]);
            // end

            DB::commit();
            return $requesterOrder;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }

    public static function approve(Order  $order, float $charge = 0)
    {
        try {
            DB::beginTransaction();
            $amount = ($order->amount);
            $charge = $order->charge ?? $charge;
            $amountAndCharge = ($amount) + ($charge);
            $toWallet = $order->toWallet->address;
            $fromWallet = $order->fromWallet->address;

            $fromWalletData = Wallet::where("address", $fromWallet)
                ->where("balance", ">=", ($amountAndCharge))->first();

            // check is fromWallet exist and valid
            // if valid and also exist , add the amount to fromWallet balance 
            if ($fromWalletData) {
                $fromWalletData->increment("balance");
                $fromWalletData->save();
            } else throw new TransactionException("Wallet address not found or Invalid!");
            // end

            $toWalletData = Wallet::where("address", $toWallet)->first();

            // check is toWallet valid/exist and balance enough for doing this transfer process
            // if exist -> subtract the toWallet balance
            if ($toWalletData && (floatval($toWalletData->balance) >= $amountAndCharge)) {
                $toWalletData->decrement("balance", $amountAndCharge);
                $toWalletData->save();
            } else throw new TransactionException("Wallet balance is not enough!");
            // end 

            // create -> order & transaction completed_at from "Carbon::now()" 
            $completedAt = now()->toDateTimeString();
            // update the order model object 
            $order->status = Order::STATUS_COMPLETED;
            $order->completed_at = $completedAt;
            // update the order databases 
            Order::where(fn ($q) => $q
                // ->where("user_id", "!=", $order->user_id)
                ->where('transaction_id', $order->transaction_id)  /**/)
                ->update([
                    "status" => Order::STATUS_COMPLETED,
                    "completed_at" => $completedAt
                ]);
            // end 

            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $order->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_COMPLETED,
                ]);
            // end

            DB::commit();
            return $order; // return the updated order object 
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }

    public static function reject(Order  $order)
    {
        try {
            DB::beginTransaction();

            // update the order model object 
            $order->status = Order::STATUS_REJECTED;
            // update the order databases 
            Order::where(fn ($q) => $q
                ->where('transaction_id', $order->transaction_id)  /**/)
                ->update([
                    "status" => Order::STATUS_REJECTED,
                ]);
            // end 
            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $order->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_REJECTED,
                ]);
            // end

            DB::commit();
            return $order; // return the updated order object 
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }
}
