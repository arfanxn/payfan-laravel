<?php

namespace App\Actions;

use App\Exceptions\TransactionException;
use App\Helpers\StrHelper;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SendMoneyAction extends TransactionActionAbstract
{
    public function exec(): array |TransactionException | QueryException| Exception
    {
        try {
            DB::beginTransaction();
            $amount = ($this->amount);
            $charge = $this->charge ?? 0;
            $amountAndCharge = ($amount) + ($charge);
            $note = $this->note ?? "";
            $toWallet = $this->toWallet;
            $fromWallet = $this->fromWallet;

            // if the user send to the same wallet, let say wallet-id-1 send to wallet-id-1 ,throw an error.
            if ($fromWallet ==  $toWallet) {
                throw new TransactionException("Can't sent to the same Wallet!");
            }
            // end

            // minumum transfer must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) {
                throw new TransactionException("Minimum Transaction is $0.10");
            }
            // end

            $fromWalletData = Wallet::where("address", $fromWallet)
                ->where("balance", ">=", ($amountAndCharge))->first();

            // check is fromWallet valid/exist and balance enough for doing this transfer process
            // if exist -> subtract the fromWallet balance
            if ($fromWalletData) {
                $fromWalletData->decrement("balance", $amountAndCharge);
                $fromWalletData->save();
            } else {
                throw new TransactionException("Wallet balance is not enough!");
            }
            // end

            $toWalletData = Wallet::where("address", $toWallet)->first();

            // check is toWallet exist and valid
            // if valid and also exist , add the amount to toWallet balance
            if ($toWalletData) {
                $toWalletData->increment("balance", $amount);
                $toWalletData->save();
            } else {
                throw new TransactionException("Wallet address not found or Invalid!");
            }
            // end

            // make the transaction and  orders  
            $now = now();
            $transactionID = strtoupper(StrHelper::random(14)) . $now->timestamp;
            Transaction::create([
                "id" => $transactionID,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "amount" => $amount,
                "charge" => $charge,
                "created_at" => $now->toDateTimeString(),
                "status" => Transaction::STATUS_COMPLETED,
            ]); // end
            $senderOrder = [ // create a new order for sender account
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $fromWalletData->user_id,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Order::TYPE_SENDING_MONEY,
                "status" => Order::STATUS_COMPLETED,
                "amount" => $amount,
                "charge" => $charge,
                "started_at" => $now->toDateTimeString(),
                "completed_at" => $now->toDateTimeString(),
                "updated_at" => null,
            ];
            Order::insert([
                $senderOrder /*create a new order for sender account*/,
                [    // create a new order for receiver account
                    "id" => strtoupper(StrHelper::random(14)) . $now->timestamp,
                    "user_id" => $toWalletData->user_id,
                    "from_wallet" => $fromWalletData->id,
                    "to_wallet" => $toWalletData->id,
                    "transaction_id" => $transactionID,
                    "note" => $note,
                    "type" => Order::TYPE_RECEIVING_MONEY,
                    "status" => Order::STATUS_COMPLETED,
                    "amount" => $amount,
                    "charge" => 0,
                    "started_at" => $now->toDateTimeString(),
                    "completed_at" => $now->toDateTimeString(),
                    "updated_at" => null,
                ]
            ]);

            DB::commit();
            return $senderOrder;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }
}
