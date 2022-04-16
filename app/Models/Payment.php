<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
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
        "completed_at",
        "created_at",
        "updated_at",
    ];

    public $timestamps = true;
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        "completed_at" => "datetime",
        'created_at' => "datetime",
        "updated_at" => "datetime",
    ];

    public const
        STATUS_COMPLETED = "COMPLETED",
        STATUS_PENDING =  "PENDING",
        STATUS_REJECTED = "REJECTED",
        STATUS_FAILED = "FAILED";

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
