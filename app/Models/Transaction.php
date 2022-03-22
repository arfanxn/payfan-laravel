<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = "COMPLETED", STATUS_PENDING =  "PENDING", STATUS_REJECTED = "REJECTED",   STATUS_FAILED = "FAILED";

    public const MINIMUM_AMOUNT = "0.10", MAXIMUM_AMOUNT = "100000000.00";

    public  $incrementing = false;

    protected $casts = [
        'id' => 'string'
    ];

    protected $fillable = [
        "id", "from_wallet", "to_wallet",
        "amount", "charge",
    ];

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, "to_wallet", 'id');
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, "from_wallet", 'id');
    }
}
