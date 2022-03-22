<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        "id",
        "user_id",
        "from_wallet",
        "to_wallet",
        "transaction_id",
        "note",
        "type",
        "status",
        "amount",
        "charge",
        "started_at",
        "completed_at",
        "updated_at",
    ];

    public $incrementing = false;

    protected $casts = [
        'id' => 'string'
    ];

    public const
        STATUS_COMPLETED = "COMPLETED",
        STATUS_PENDING =  "PENDING",
        STATUS_REJECTED = "REJECTED",
        STATUS_FAILED = "FAILED";
    /*  STATUS_WAIITING_FOR_APPROVAL = "WAIITING_FOR_APPROVAL",  */


    public const
        TYPE_SENDING_MONEY = 'SENDING MONEY',
        TYPE_MAKE_REQUEST_MONEY = 'MAKE REQUEST MONEY',
        TYPE_PENDING_REQUEST_MONEY = 'PENDING REQUEST MONEY',
        TYPE_RECEIVING_MONEY = 'RECEIVING MONEY',
        TYPE_GIFT = "GIFT";
}
