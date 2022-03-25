<?php

namespace App\Repositories;

use Illuminate\Support\Str;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;


class OrderRepository
{
    public static function filters(array $options = [], ?EloquentBuilder $orderQuery = null): EloquentBuilder
    {
        $orderQuery = is_null($orderQuery) ? Order::query() : $orderQuery;

        ['keyword' => $keyword, "start_at" => $start_at, "end_at" => $end_at, "status" => $status, "type" => $type] = $options;
        if ($keyword) {
            $orderQuery  = $orderQuery->where(fn ($q)
            => $q->where("id", "LIKE", "$keyword%")->orWhere("note", "LIKE", "$keyword%"))->orWhere("amount", "LIKE", "$keyword%");
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
            $type = strtoupper($type);
            if (Str::contains(Order::TYPE_SENDING_MONEY, $type))
                $orderQuery->where("type", Order::TYPE_SENDING_MONEY);

            else if (Str::contains(Order::TYPE_RECEIVING_MONEY, $type))
                $orderQuery->where("type", Order::TYPE_RECEIVING_MONEY);

            else if (Str::contains(Order::TYPE_REQUESTING_MONEY, $type))
                $orderQuery->where("type", Order::TYPE_REQUESTING_MONEY);

            else if (Str::contains(Order::TYPE_REQUESTED_MONEY, $type))
                $orderQuery->where("type", Order::TYPE_REQUESTED_MONEY);

            else if (Str::contains(Order::TYPE_GIFT_FROM(/**/), explode(" ", $type)/**/)  /**/)
                $orderQuery->where(fn ($q) => $q
                    ->where("type", "GIFT")
                    ->orWhere("type", Order::TYPE_GIFT_FROM()));
        }

        return $orderQuery;
    }
}
