<?php

namespace App\Actions;

use App\Events\PaymentStatusCompletedEvent;
use App\Events\WalletUpdatedEvent;
use App\Exceptions\TransactionException;
use App\Helpers\StrHelper;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\Transactions\ApprovedRequestMoneyNotification;
use App\Notifications\Transactions\ApprovingRequestMoneyNotification;
use App\Notifications\Transactions\MakeRequestingMoneyNotification;
use App\Notifications\Transactions\NewRequestedMoneyNotification;
use App\Notifications\Transactions\RejectedRequestMoneyNotification;
use App\Notifications\Transactions\RejectingRequestMoneyNotification;
use App\Repositories\ContactRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestMoneyAction extends TransactionActionAbstract
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
            $requestingOrder = [ // create a new order for requester money/payment account
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $requesterWalletData->user_id,
                "from_wallet" => $requesterWalletData->id,
                "to_wallet" => $beingRequestedWalletData->id,
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
            $requestedOrder = [ // create a new order for  account that being requested money
                "id" => strtoupper(StrHelper::random(14))  . $now->timestamp,
                "user_id" => $beingRequestedWalletData->user_id,
                "from_wallet" => $requesterWalletData->id,
                "to_wallet" => $beingRequestedWalletData->id,
                "transaction_id" => $transactionID,
                "note" => $note,
                "type" => Order::TYPE_REQUESTED_MONEY,
                "status" => Order::STATUS_PENDING,
                "amount" => $amount,
                "charge" => $charge,
                "started_at" => $now->toDateTimeString(),
                "completed_at" => null,
                "updated_at" => null,
            ];

            Order::insert([$requestingOrder, $requestedOrder]);
            // end

            DB::commit();

            Notification::send(
                User::query()->where("id", $requestingOrder['user_id'])->first(),
                new MakeRequestingMoneyNotification(new Order($requestingOrder))
            );
            Notification::send(
                User::query()->where("id", $requestedOrder['user_id'])->first()  /**/,
                new NewRequestedMoneyNotification(new Order($requestedOrder))
            );

            return $requestingOrder;
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
            $beingRequestedWallet = $order->toWallet->address;
            $requesterWallet = $order->fromWallet->address;

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

            // create -> order & transaction completed_at from "Carbon::now()" 
            $completedAt = now()->toDateTimeString();
            // update the order model object 
            $order->status = Order::STATUS_COMPLETED;
            $order->completed_at = $completedAt;
            // update the order databases and get 
            $approvedOrder = tap(
                Order::where(fn ($q) => $q
                    ->where('transaction_id', $order->transaction_id)  /**/),
                function ($orderQuery)  use ($completedAt, $order) {
                    // update 2 related order 
                    $orderQuery->update([
                        "status" => Order::STATUS_COMPLETED,
                        "completed_at" => $completedAt
                    ]);
                    // get where clause user_id not equal to order->user_id
                    return $orderQuery->where("user_id", "!=", $order->user_id);
                }
            );
            // end

            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $order->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_COMPLETED,
                ]);
            // end

            $approvedOrder = $approvedOrder->first();

            ContactRepository::incrementAndUpdate_LastTransactionAndTotalTransaction_whereOwnerIdOrSavedId(
                [$order["user_id"] ?? $order->user_id, $approvedOrder->user_id ?? $approvedOrder['user_id']/**/],
            );
            DB::commit();

            Notification::send(
                User::query()->where("id", $order->user_id)->first()  /**/,
                new ApprovingRequestMoneyNotification($order)
            );
            Notification::send(
                User::query()->where("id", $approvedOrder->user_id)->first(),
                new ApprovedRequestMoneyNotification($approvedOrder)
            );

            broadcast(new WalletUpdatedEvent($requesterWalletData))->toOthers();
            broadcast(new PaymentStatusCompletedEvent(new Order($approvedOrder)))->toOthers();

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
            $rejectedOrder = tap(
                Order::where(fn ($q) => $q
                    ->where('transaction_id', $order->transaction_id)  /**/),
                function ($userQuery) use ($order) {
                    // update 2 related order 
                    $userQuery->update([
                        "status" => Order::STATUS_REJECTED,
                    ]);
                    // get where clause user_id not equal to order->user_id
                    return ($userQuery->where("user_id", "!=", $order->user_id));
                }
            );
            // end 

            // update the transaction status 
            Transaction::where(fn ($q) => $q->where("id", $order->transaction_id))
                ->update([
                    "status" => Transaction::STATUS_REJECTED,
                ]);
            // end

            $rejectedOrder = $rejectedOrder->first();
            DB::commit();

            Notification::send(
                User::query()->where("id", $order->user_id)->first(),
                new RejectingRequestMoneyNotification($order)
            );
            Notification::send(
                User::query()->where("id", $rejectedOrder->user_id)->first()  /**/,
                new RejectedRequestMoneyNotification($rejectedOrder)
            );

            return $order; // return the updated order object 
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return $e;
        }
    }
}
