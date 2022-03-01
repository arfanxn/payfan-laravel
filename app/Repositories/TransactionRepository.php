<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;

class TransactionRepository
{
    public static function lastTransactionWith(UserWallet $wallet1,  UserWallet $wallet2)
    {
        $lastTransaction = Transaction::query()->where(
            fn ($query) => $query->where("from_wallet", $wallet1->id)->orWhere("from_wallet", $wallet2->id)
        )->where(
            fn ($query) => $query->where("to_wallet", $wallet1->id)->orWhere("to_wallet", $wallet2->id)
        )->orderBy("completed_at", 'desc');

        return $lastTransaction;
    }
}
