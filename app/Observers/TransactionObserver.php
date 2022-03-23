<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Wallet;

class TransactionObserver
{
    public function saved(Transaction $transaction)
    {
        // If the transaction relation not loaded do the folowing action below
        // if (!$transaction->relationLoaded("toWallet"))
        //     $transaction = $transaction->load("toWallet");
        // if (!$transaction->relationLoaded("fromWallet"))
        //     $transaction = $transaction->load("fromWallet");

        if ($transaction->status == Transaction::STATUS_COMPLETED) {
            Wallet::query()->where(fn ($q) =>
            $q->whereIn("id", [$transaction->from_wallet, $transaction->to_wallet]))
                ->increment("total_transaction", 1, ["last_transaction" => now()->toDateTimeString()] /**/);
        }
    }
}
