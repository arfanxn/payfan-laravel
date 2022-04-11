<?php

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ContactRepository
{
    public static function filters(array $options = [], ?EloquentBuilder $contactQuery = null) // : EloquentBuilder
    {
        $contactQuery = is_null($contactQuery) ? Contact::query() : $contactQuery;

        [   /*'filter' => $filter,*/
            "order_by" => $order_by, "blocked" => $blocked, "added" => $added, "favorited" => $favorited
        ] = $options;

        if ($blocked || $added || $favorited) {
            $contactQuery = $contactQuery->where(function ($q) use ($added, $favorited, $blocked) {
                if ($added)  $q->where("status",  Contact::STATUS_ADDED);
                if ($favorited) $q->orWhere("status",  Contact::STATUS_FAVORITED);
                if ($blocked) $q->orWhere("status",  Contact::STATUS_BLOCKED);

                return $q;
            });
        }

        if ($order_by) {
            $order_by  =  is_array($order_by) ? $order_by : explode(':', $order_by);
            $columnName = $order_by[0]; // "total_transaction" 
            $orderingType = $order_by[1]; // asc or desc 

            $contactQuery = $contactQuery->orderBy($columnName, $orderingType);
        }

        return $contactQuery;
    }

    public static function where_OwnID_andWhereIn_SavedID(int $owner_id, array $saved_ids = []): EloquentBuilder
    {
        $contactQuery = Contact::query()->where("status", "!=", Contact::STATUS_BLOCKED)
            ->where(function ($query)  use ($saved_ids, $owner_id) {
                return $query->whereIn("saved_id", $saved_ids)->where("owner_id", $owner_id);
            });
        return $contactQuery;
    }

    public static function
    incrementAndUpdate_LastTransactionAndTotalTransaction_whereOwnerIdOrSavedId(array $ids, ?EloquentBuilder $contactQuery = null)
    {
        $contactQuery = is_null($contactQuery) ? Contact::query() : $contactQuery;
        $contactQuery = $contactQuery->where(fn ($q) => $q->whereIn("owner_id", $ids)->whereIn("saved_id", $ids));

        return $contactQuery->increment("total_transaction", 1, [
            "last_transaction" => now()->toDateTimeString(),
        ]);
    }
}
