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
        TYPE_RECEIVING_MONEY = 'RECEIVING MONEY',
        /**/
        TYPE_REQUESTING_MONEY = 'REQUESTING MONEY',
        TYPE_REQUESTED_MONEY = 'REQUESTED MONEY';
    public final static function TYPE_GIFT_FROM(string $from = null)
    {
        $from = is_null($from) ? config("app.name") : $from;
        return "GIFT FROM $from";
    }


    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, "to_wallet", 'id');
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, "from_wallet", 'id');
    }
}
