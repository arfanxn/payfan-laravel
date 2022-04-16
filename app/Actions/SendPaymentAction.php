<?php

namespace App\Actions;

use App\Events\PaymentSavedEvent;
use App\Events\PaymentStatusCompletedEvent;
use App\Events\WalletUpdatedEvent;
use App\Exceptions\TransactionException;
use App\Helpers\StrHelper;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\Transactions\ReceivingPaymentNotification;
use App\Notifications\Transactions\SendPaymentNotification;
use App\Repositories\ContactRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendPaymentAction extends TransactionActionAbstract
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
                $fromWalletData->increment("total_transaction", 1);
                $fromWalletData->last_transaction  = now()->toDateTimeString();
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
                $toWalletData->increment("total_transaction", 1);
                $toWalletData->last_transaction  = now()->toDateTimeString();
                $toWalletData->save();
            } else {
                throw new TransactionException("Wallet address not found or Invalid!");
            }
            // end

            // make the transaction and  payments  
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
            $senderPayment = [ // create a new payment for sender account
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $fromWalletData->user_id,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Payment::TYPE_SENDING,
                "status" => Payment::STATUS_COMPLETED,
                "amount" => $amount,
                "charge" => $charge,
                "created_at" => $now->toDateTimeString(),
                "completed_at" => $now->toDateTimeString(),
                "updated_at" => $now->toDateTimeString(),
            ];
            $receiverPayment = [
                "id" => strtoupper(StrHelper::random(14)) . $now->timestamp,
                "user_id" => $toWalletData->user_id,
                "from_wallet" => $fromWalletData->id,
                "to_wallet" => $toWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Payment::TYPE_RECEIVING,
                "status" => Payment::STATUS_COMPLETED,
                "amount" => $amount,
                "charge" => 0,
                "created_at" => $now->toDateTimeString(),
                "completed_at" => $now->toDateTimeString(),
                "updated_at" => $now->toDateTimeString(),
            ];

            Payment::insert([
                $senderPayment /*create a new payment for sender account*/,
                $receiverPayment // create a new payment for receiver account 
            ]);

            ContactRepository::incrementAndUpdate_LastTransactionAndTotalTransaction_whereOwnerIdOrSavedId(
                [$senderPayment['user_id'], $receiverPayment['user_id']/**/],
            );

            DB::commit();

            Notification::send(
                User::where("id", $senderPayment['user_id'])->first(),
                new SendPaymentNotification(new \App\Models\Payment($senderPayment))
            );
            Notification::send(
                User::where("id", $receiverPayment['user_id'])->first(),
                new ReceivingPaymentNotification(new \App\Models\Payment($senderPayment))
            );

            // broadcast payment created or updated event (PaymentSavedEvent) 
            broadcast(new PaymentSavedEvent(new Payment($receiverPayment)));
            broadcast(new PaymentSavedEvent(new Payment($senderPayment))); // broadcast to self 

            // broadcast updatedWallet
            broadcast(new WalletUpdatedEvent($toWalletData))->toOthers();

            // broadcast payment status completed event 
            broadcast(new PaymentStatusCompletedEvent(new Payment($receiverPayment)));


            return $senderPayment;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }
}
