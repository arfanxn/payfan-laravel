<?php

namespace App\Repositories;

use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;


class PaymentRepository
{
    public static function filters(array $options = [], ?EloquentBuilder $paymentQuery = null): EloquentBuilder
    {
        $paymentQuery = is_null($paymentQuery) ? Payment::query() : $paymentQuery;

        ['keyword' => $keyword, "start_at" => $start_at, "end_at" => $end_at, "status" => $status, "type" => $type] = $options;
        if ($keyword) {
            $paymentQuery  = $paymentQuery->where(
                fn ($q) => $q->where("id", "LIKE", "$keyword%")
                    ->orWhere("transaction_id", "LIKE", "$keyword%")
                    ->orWhere("note", "LIKE", "$keyword%")
                    ->orWhere("amount", "LIKE", "$keyword%")
            );
        }
        if ($start_at) {
            $paymentQuery = $paymentQuery->where("started_at", ">=", $start_at);
        }
        if ($end_at) {
            $paymentQuery = $paymentQuery->where("started_at", "<=", $end_at);
        }

        if ($status) {
            switch (strtoupper($status)) {
                case Payment::STATUS_COMPLETED:
                    $paymentQuery->where("status", Payment::STATUS_COMPLETED);
                    break;
                case Payment::STATUS_PENDING:
                    $paymentQuery->where("status", Payment::STATUS_PENDING);
                    break;
                case Payment::STATUS_REJECTED:
                    $paymentQuery->where("status", Payment::STATUS_REJECTED);
                    break;
                case Payment::STATUS_FAILED:
                case "FAIL":
                    $paymentQuery->where("status", Payment::STATUS_FAILED);
                    break;
            }
        }
        if ($type) {
            $type = strtoupper($type);
            if (Str::contains(Payment::TYPE_SENDING, $type))
                $paymentQuery->where("type", Payment::TYPE_SENDING);

            else if (Str::contains(Payment::TYPE_RECEIVING, $type))
                $paymentQuery->where("type", Payment::TYPE_RECEIVING);

            else if (Str::contains(Payment::TYPE_REQUESTING, $type))
                $paymentQuery->where("type", Payment::TYPE_REQUESTING);

            else if (Str::contains(Payment::TYPE_REQUESTED, $type))
                $paymentQuery->where("type", Payment::TYPE_REQUESTED);

            else if (Str::contains(Payment::TYPE_GIFT_FROM(/**/), explode(" ", $type)/**/)  /**/)
                $paymentQuery->where(fn ($q) => $q
                    ->where("type", "GIFT")
                    ->orWhere("type", Payment::TYPE_GIFT_FROM()));
        }

        return $paymentQuery;
    }

    public static function latestWith(Wallet $wallet1, Wallet $wallet2)
    {
        $lastTransaction = Payment::query()->where(
            fn ($query) => $query->where("from_wallet", $wallet1->id)->orWhere("from_wallet", $wallet2->id)
        )->where(
            fn ($query) => $query->where("to_wallet", $wallet1->id)->orWhere("to_wallet", $wallet2->id)
        )->orderBy("updated_at", 'desc');

        return $lastTransaction;
    }
}
