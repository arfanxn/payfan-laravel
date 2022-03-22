<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use App\Models\Transaction;
use App\Models\Wallet;

class OrderRepository
{
    public static function filters(array $options = [], ?EloquentBuilder $orderQuery = null): EloquentBuilder
    {
        $orderQuery = is_null($orderQuery) ? Order::query() : $orderQuery;

        ['keyword' => $keyword, "start_at" => $start_at, "end_at" => $end_at, "status" => $status, "type" => $type] = $options;
        if ($keyword) {
            $orderQuery  = $orderQuery->where(fn ($q)
            => $q->where("tx_hash", "LIKE", "$keyword%")->orWhere("note", "LIKE", "$keyword%"));
        }
        if ($start_at) {
            $orderQuery = $orderQuery->where("started_at", ">=", $start_at);
        }
        if ($end_at) {
            $orderQuery = $orderQuery->where("started_at", "<=", $end_at);
        }

        if ($status) {
            switch (strtoupper($status)) {
                case Order::STATUS_COMPLETED:
                    $orderQuery->where("status", Order::STATUS_COMPLETED);
                    break;
                case Order::STATUS_PENDING:
                    $orderQuery->where("status", Order::STATUS_PENDING);
                    break;
                case Order::STATUS_REJECTED:
                    $orderQuery->where("status", Order::STATUS_REJECTED);
                    break;
                case Order::STATUS_FAILED:
                case "FAIL":
                    $orderQuery->where("status", Order::STATUS_FAILED);
                    break;
            }
        }
        if ($type) {
            switch (strtoupper($type)) {
                case Order::TYPE_SENDING_MONEY:
                    $orderQuery->where("type", Order::TYPE_SENDING_MONEY);
                    break;
                case Order::TYPE_REQUESTING_MONEY:
                    $orderQuery->where("type", Order::TYPE_REQUESTING_MONEY);
                    break;
                case Order::TYPE_REQUESTED_MONEY:
                    $orderQuery->where("type", Order::TYPE_REQUESTED_MONEY);
                    break;
                case Order::TYPE_GIFT:
                case "RECEIVED":
                case "RECEIVING MONEY":
                case "RECEIVE":
                    $orderQuery->where(fn ($q) => $q
                        ->where("type", Order::TYPE_GIFT)
                        ->orWhere("type", Order::TYPE_RECEIVING_MONEY));
                    break;
            }
        }

        return $orderQuery;
    }
}
