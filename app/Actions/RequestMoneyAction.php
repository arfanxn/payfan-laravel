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

            // if the user send to the same wallet, let say wallet-id-1 send to wallet-id-1 ,throw an error.
            if ($fromWallet ==  $toWallet)
                throw new TransactionException("Can't  request to the same Wallet!");
            // end

            // minumum request must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) throw new TransactionException("Minimum Transaction is $0.10");
            // end

            $fromWalletData = Wallet::where("address", $fromWallet)->first();

            $toWalletData = Wallet::where("address", $toWallet)->first();

            // make the transaction history / transaction invoice 
            // tx_hash is the transaction uniq_id
            $tx_hash = strtoupper(StrHelper::random(10)) . preg_replace("/[^0-9]+/",  "", now()->toDateTimeString());
            $txCreate = [
                "tx_hash" => $tx_hash,
                "from_wallet" => $fromWalletData->id,
                // "status" => Transaction::STATUS_PENDING,
                // "type" => Transaction::TYPE_REQUEST_MONEY,
                "to_wallet" => $toWalletData->id,
                "amount" => $amount,
                "note" => $note,
            ];
            $now = now();
            $transactionID = strtoupper(StrHelper::random(14)) . $now()->timestamp;
            $requesterOrder = [ // create a new order for requester money/payment account
                "id" => strtoupper(StrHelper::random(14))  . $now()->timestamp,
                "user_id" => $fromWalletData->user_id,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Order::TYPE_MAKE_REQUEST_MONEY,
                "status" => Order::STATUS_PENDING,
                "amount" => $amount,
                "started_at" => $now->toDateTimeString(),
                "completed_at" => null,
                "updated_at" => null,
            ];
            Order::insert([
                $requesterOrder,
                [   // create a new order for  account that being requested request money
                    "id" => strtoupper(StrHelper::random(14))  . $now()->timestamp,
                    "user_id" => $toWalletData->user_id,
                    "from_wallet" => $fromWalletData->id,
                    "to_wallet" => $toWalletData->id,
                    "transaction_id" => $transactionID,
                    "note" => $note,
                    "type" => Order::TYPE_PENDING_REQUEST_MONEY,
                    "status" => Order::STATUS_PENDING,
                    "amount" => $amount,
                    "charge" => $charge,
                    "started_at" => $now->toDateTimeString(),
                    "completed_at" => null,
                    "updated_at" => null,
                ]
            ]);

            DB::commit();
            return $requesterOrder;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            $exceptionClass = get_class($e);
            throw new $exceptionClass($e->getMessage());
        }
    }

    public static function approve(Transaction  $transaction, float $charge = 0)
    {
        try {
            DB::beginTransaction();
            $amount = ($transaction->amount);
            $charge = $transaction->charge ?? $charge;
            $amountAndCharge = ($amount) + ($charge);
            $toWallet = $transaction->toWallet;
            $fromWallet = $transaction->fromWallet;

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
            if ($toWalletData) {
                $toWalletData->decrement("balance", $amountAndCharge);
                $toWalletData->save();
            } else throw new TransactionException("Wallet balance is not enough!");
            // end 

            // update the transaction status
            // $transaction->status = Transaction::STATUS_COMPLETED;
            // $transaction->completed_at = now()->toDateTimeString;
            // $transaction->save();
            // end 

            DB::commit();
            return $transaction;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            $exceptionClass = get_class($e);
            throw new $exceptionClass($e->getMessage());
        }

        /*    catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            throw new Exception($e);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }    */
    }
}
