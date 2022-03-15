<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;

class TransactionRepository
{
    public static function filters(array $options = [], ?EloquentBuilder $transactionQuery = null): EloquentBuilder
    {
        $transactionQuery =  is_null($transactionQuery) ? Transaction::query() : $transactionQuery;

        ['keyword' => $keyword, "start_at" => $start_at, "end_at" => $end_at, "status" => $status, "type" => $type] = $options;
        if ($keyword) {
            $transactionQuery  = $transactionQuery->where(fn ($q)
            => $q->where("tx_hash", "LIKE", "$keyword%")->orWhere("note", "LIKE", "$keyword%"));
        }
        if ($start_at) {
            $transactionQuery = $transactionQuery->where("started_at", ">=", $start_at);
        }
        if ($end_at) {
            $transactionQuery = $transactionQuery->where("started_at", "<=", $end_at);
        }

        if ($status) {
            switch (strtoupper($status)) {
                case Transaction::STATUS_COMPLETED:
                    $transactionQuery = $transactionQuery->where("status", Transaction::STATUS_COMPLETED);
                    break;
                case Transaction::STATUS_PENDING:
                    $transactionQuery = $transactionQuery->where("status", Transaction::STATUS_PENDING);
                    break;
                case Transaction::STATUS_REJECTED:
                    $transactionQuery = $transactionQuery->where("status", Transaction::STATUS_REJECTED);
                    break;
                case Transaction::STATUS_FAILED:
                case "FAIL":
                    $transactionQuery = $transactionQuery->where("status", Transaction::STATUS_FAILED);
                    # code...
                    break;
            }
        }
        if ($type) {
            switch (strtoupper($type)) {
                case Transaction::TYPE_SEND_MONEY:
                    $transactionQuery = $transactionQuery->where("type", Transaction::TYPE_SEND_MONEY);
                    break;
                case Transaction::TYPE_REQUEST_MONEY:
                    $transactionQuery = $transactionQuery->where("type", Transaction::TYPE_REQUEST_MONEY);
                    break;
                case Transaction::TYPE_REWARD:
                case "RECEIVED":
                case "RECEIVE":
                    $transactionQuery = $transactionQuery->where("type", Transaction::TYPE_REWARD);
                    break;
            }
        }

        return $transactionQuery;
    }

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
