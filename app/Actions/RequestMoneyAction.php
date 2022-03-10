<?php

namespace App\Actions;

use App\Helpers\StrHelper;
use App\Models\Transaction;
use App\Models\UserWallet;
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
            $toWallet = $this->toWallet;
            $fromWallet = $this->fromWallet;

            // if the user send to the same wallet, let say wallet-id-1 send to wallet-id-1 ,throw an error.
            if ($fromWallet ==  $toWallet)
                throw new Exception("Can't  request to the same Wallet!");
            // end

            // minumum request must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) throw new Exception("Minimum Transaction is $0.10");
            // end

            $fromWalletData = UserWallet::where("address", $fromWallet)->first();

            $toWalletData = UserWallet::where("address", $toWallet)->first();

            // make the transaction history / transaction invoice 
            // tx_hash is the transaction uniq_id
            $tx_hash = strtoupper(StrHelper::random(10)) . preg_replace("/[^0-9]+/",  "", now()->toDateTimeString());
            $txCreate = [
                "tx_hash" => $tx_hash,
                "from_wallet" => $fromWalletData->id,
                "status" => Transaction::STATUS_PENDING,
                "type" => Transaction::TYPE_REQUEST_MONEY,
                "to_wallet" => $toWalletData->id,
                "amount" => $amount,
                "note" => $note,
            ];
            Transaction::create($txCreate); // end 

            DB::commit();
            return $txCreate;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            throw new Exception($e);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
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

            $fromWalletData = UserWallet::where("address", $fromWallet)
                ->where("balance", ">=", ($amountAndCharge))->first();

            // check is fromWallet exist and valid
            // if valid and also exist , add the amount to fromWallet balance 
            if ($fromWalletData) {
                $fromWalletData->increment("balance");
                $fromWalletData->save();
            } else throw new Exception("Wallet address not found or Invalid!");
            // end

            $toWalletData = UserWallet::where("address", $toWallet)->first();

            // check is toWallet valid/exist and balance enough for doing this transfer process
            // if exist -> subtract the toWallet balance
            if ($toWalletData) {
                $toWalletData->decrement("balance", $amountAndCharge);
                $toWalletData->save();
            } else throw new Exception("Wallet balance is not enough!");
            // end 

            // update the transaction status
            $transaction->status = Transaction::STATUS_COMPLETED;
            $transaction->completed_at = now()->toDateTimeString;
            $transaction->save();
            // end 

            DB::commit();
            return $transaction;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            throw new Exception($e);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e);
        }
    }
}
