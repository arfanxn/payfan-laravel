<?php

namespace App\Actions;

use App\Helpers\StrHelper;
use App\Models\Transaction;
use App\Models\UserWallet;
use Exception;
use Illuminate\Support\Facades\DB;

class SendMoneyAction extends TransactionActionAbstract
{
    public function exec()
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
            if ($fromWallet ==  $toWallet)
                throw new Exception("Can't sent to the same Wallet!");
            // end

            // minumum transfer must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) throw new Exception("Minimum Transaction is $0.10");
            // end

            $fromWalletData = UserWallet::where("address", $fromWallet)
                ->where("balance", ">=", ($amountAndCharge))->first();

            // check is fromWallet valid/exist and balance enough for doing this transfer process
            // if exist -> subtract the fromWallet balance
            if ($fromWalletData) {
                $fromWalletData->decrement("balance", $amountAndCharge);
                $fromWalletData->save();
            } else throw new Exception("Wallet balance is not enough!");
            // end

            $toWalletData = UserWallet::where("address", $toWallet)->first();

            // check is toWallet exist and valid
            // if valid and also exist , add the amount to toWallet balance 
            if ($toWalletData) {
                $toWalletData->increment("balance", $amount);
                $toWalletData->save();
            } else throw new Exception("Wallet address not found or Invalid!");
            // end 


            // make the transaction history / transaction invoice 
            // tx_hash is the transaction uniq_id
            $tx_hash = strtoupper(StrHelper::random(10)) . preg_replace("/[^0-9]+/",  "", now()->toDateTimeString());
            $txCreate = [
                "tx_hash" => $tx_hash,
                "from_wallet" => $fromWalletData->id,
                "status" => Transaction::STATUS_COMPLETED,
                "type" => Transaction::TYPE_SEND_MONEY,
                "to_wallet" => $toWalletData->id,
                "completed_at" => now()->toDateTimeString(), //// 
                "amount" => $amount,
                "charge" => $charge,
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
}
