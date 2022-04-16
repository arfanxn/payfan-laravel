<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // this tables has not having column below 
    public const CREATED_AT = null, UPDATED_AT = null;

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
        'id' => 'string',
        'started_at' => "datetime",
        "completed_at" => "datetime",
        "updated_at" => "datetime",
    ];
    // protected $dates = [
    //     'started_at',
    //     "completed_at",
    //     "updated_at",
    // ];

    public const
        STATUS_COMPLETED = "COMPLETED",
        STATUS_PENDING =  "PENDING",
        STATUS_REJECTED = "REJECTED",
        STATUS_FAILED = "FAILED";
    /*  STATUS_WAIITING_FOR_APPROVAL = "WAIITING_FOR_APPROVAL",  */


    public const
        TYPE_SENDING  = 'SENDING PAYMENT',
        TYPE_RECEIVING  = 'RECEIVING PAYMENT',
        /**/
        TYPE_REQUESTING  = 'REQUESTING PAYMENT',
        TYPE_REQUESTED  = 'REQUESTED PAYMENT';
    public final static function TYPE_GIFT_FROM(string $from = null): string
    {
        $from = is_null($from) ? config("app.name") : $from;
        return strtoupper("GIFT FROM $from");
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
