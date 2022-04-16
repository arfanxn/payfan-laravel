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
use App\Notifications\Transactions\ApprovedRequestPaymentNotification;
use App\Notifications\Transactions\ApprovingRequestPaymentNotification;
use App\Notifications\Transactions\MakeRequestingPaymentNotification;
use App\Notifications\Transactions\NewRequestedPaymentNotification;
use App\Notifications\Transactions\RejectedRequestPaymentNotification;
use App\Notifications\Transactions\RejectingRequestPaymentNotification;
use App\Repositories\ContactRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestPaymentAction extends TransactionActionAbstract
{
    public function make()
    {
        try {
            DB::beginTransaction();
            $amount = ($this->amount);
            $note = $this->note ?? "";
            $charge = $this->charge ?? 0;
            $beingRequestedWallet = $this->toWallet;
            $requesterWallet = $this->fromWallet;

            // if the user requesting to the same wallet, let say wallet-id-1 requeesting to wallet-id-1 ,throw an error.
            if ($requesterWallet ==  $beingRequestedWallet)
                throw new TransactionException("Can't make a request to the same Wallet!");
            // end

            // minumum request must be at least "$0.10"
            if ($amount < floatval(Transaction::MINIMUM_AMOUNT)) throw new TransactionException("Minimum Transaction is $0.10");
            // end

            $requesterWalletData = Wallet::where("address", $requesterWallet)->first();

            $beingRequestedWalletData = Wallet::where("address", $beingRequestedWallet)->first();

            $now = now();
            $transactionID = strtoupper(StrHelper::random(14)) . $now->timestamp;
            Transaction::create([
                "id" => $transactionID,
                "from_wallet" => $requesterWalletData->id,
                "to_wallet" => $beingRequestedWalletData->id,
                "amount" => $amount,
                "charge" => $charge,
                "status" => Transaction::STATUS_PENDING,
                "created_at" => $now->toDateTimeString(),
            ]);
            $requestingPayment = [ // create a new payment for requester payment account
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $requesterWalletData->user_id,
                "from_wallet" => $requesterWalletData->id,
                "to_wallet" => $beingRequestedWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Payment::TYPE_REQUESTING,
                "status" => Payment::STATUS_PENDING,
                "charge" => $charge,
                "amount" => $amount,
                "created_at" => $now->toDateTimeString(),
                "completed_at" => null,
                "updated_at" => $now->toDateTimeString(),
            ];
            $requestedPayment = [ // create a new payment for  account that being requested payment
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $beingRequestedWalletData->user_id,
                "from_wallet" => $requesterWalletData->id,
                "to_wallet" => $beingRequestedWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Payment::TYPE_REQUESTED,
                "status" => Payment::STATUS_PENDING,
                "amount" => $amount,
                "charge" => $charge,
                "created_at" => $now->toDateTimeString(),
                "completed_at" => null,
                "updated_at" => $now->toDateTimeString(),
            ];

            Payment::insert([$requestingPayment, $requestedPayment]);
            // end

            DB::commit();

            Notification::send(
                User::query()->where("id", $requestingPayment['user_id'])->first(),
                new MakeRequestingPaymentNotification(new Payment($requestingPayment))
            );
            Notification::send(
                User::query()->where("id", $requestedPayment['user_id'])->first()  /**/,
                new NewRequestedPaymentNotification(new Payment($requestedPayment))
            );

            // broadcast to self
            broadcast(new PaymentSavedEvent(new Payment($requestingPayment)));
            // broadcast to account that being requested a payment 
            broadcast(new PaymentSavedEvent(new Payment($requestedPayment)));

            return $requestingPayment;
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }

    public static function approve(Payment  $payment, float $charge = 0)
    {
        try {
            DB::beginTransaction();
            $amount = ($payment->amount);
            $charge = $payment->charge ?? $charge;
            $amountAndCharge = ($amount) + ($charge);
            $beingRequestedWallet = $payment->toWallet->address;
            $requesterWallet = $payment->fromWallet->address;

            $requesterWalletData = Wallet::where("address", $requesterWallet)->first();

            // check is fromWallet exist and valid
            // if valid and also exist , add the amount to fromWallet balance 
            if ($requesterWalletData) {
                $requesterWalletData->increment("balance");
                $requesterWalletData->increment("total_transaction", 1);
                $requesterWalletData->last_transaction  = now()->toDateTimeString();
                $requesterWalletData->save();
            } else throw new TransactionException("Wallet address not found or Invalid!");
            // end

            $beingRequestedWalletData = Wallet::where("address", $beingRequestedWallet)->first();

            // check is toWallet valid/exist and balance enough for doing this transfer process
            // if exist -> subtract the toWallet balance
            if ($beingRequestedWalletData && (($beingRequestedWalletData->balance) >= $amountAndCharge)) {
                $beingRequestedWalletData->decrement("balance", $amountAndCharge);
                $beingRequestedWalletData->increment("total_transaction", 1);
                $beingRequestedWalletData->last_transaction  = now()->toDateTimeString();
                $beingRequestedWalletData->save();
            } else throw new TransactionException("Wallet balance is not enough!");
            // end 

            // create -> payment & transaction completed_at from "Carbon::now()" 
            $completedAt = now()->toDateTimeString();
            // update the payment model object 
            $payment->status = Payment::STATUS_COMPLETED;
            $payment->completed_at = $completedAt;
            // update the payment databases and get 
            $approvedPayment = tap(
                Payment::where(fn ($q) => $q
                    ->where('transaction_id', $payment->transaction_id)  /**/),
                function ($paymentQuery)  use ($completedAt, $payment) {
                    // update 2 related payment 
                    $paymentQuery->update([
                        "status" => Payment::STATUS_COMPLETED,
                        "completed_at" => $completedAt,
                        "updated_at" => $completedAt
                    ]);
                    // get where clause user_id not equal to payment->user_id
                    return $paymentQuery->where("user_id", "!=", $payment->user_id);
                }
            );
            // end

            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $payment->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_COMPLETED,
                ]);
            // end

            $approvedPayment = $approvedPayment->first();

            ContactRepository::incrementAndUpdate_LastTransactionAndTotalTransaction_whereOwnerIdOrSavedId(
                [$payment["user_id"] ?? $payment->user_id, $approvedPayment->user_id ?? $approvedPayment['user_id']/**/],
            );
            DB::commit();

            Notification::send(
                User::query()->where("id", $payment->user_id)->first()  /**/,
                new ApprovingRequestPaymentNotification($payment)
            );
            Notification::send(
                User::query()->where("id", $approvedPayment->user_id)->first(),
                new ApprovedRequestPaymentNotification($approvedPayment)
            );


            broadcast(new PaymentSavedEvent($payment)); // broadcast to self
            broadcast(new WalletUpdatedEvent($requesterWalletData))->toOthers();
            broadcast(new PaymentStatusCompletedEvent($approvedPayment));
            broadcast(new PaymentSavedEvent($approvedPayment));

            return $payment; // return the updated payment object 
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }

    public static function reject(Payment  $payment)
    {
        try {
            DB::beginTransaction();

            // update the payment model object 
            $payment->status = Payment::STATUS_REJECTED;
            // update the payment databases 
            $rejectedPayment = tap(
                Payment::where(fn ($q) => $q
                    ->where('transaction_id', $payment->transaction_id)  /**/),
                function ($userQuery) use ($payment) {
                    // update 2 related payment 
                    $userQuery->update([
                        "status" => Payment::STATUS_REJECTED,
                        "updated_at" => now()->toDateTimeString()
                    ]);
                    // get where clause user_id not equal to payment->user_id
                    return ($userQuery->where("user_id", "!=", $payment->user_id));
                }
            );
            // end 

            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $payment->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_REJECTED,
                ]);
            // end

            $rejectedPayment = $rejectedPayment->first();
            DB::commit();

            Notification::send(
                User::query()->where("id", $payment->user_id)->first(),
                new RejectingRequestPaymentNotification($payment)
            );
            Notification::send(
                User::query()->where("id", $rejectedPayment->user_id)->first()  /**/,
                new RejectedRequestPaymentNotification($rejectedPayment)
            );

            broadcast(new PaymentSavedEvent($payment)); // broadcast to self
            broadcast(new PaymentSavedEvent($rejectedPayment));

            return $payment; // return the updated payment object 
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }
}
