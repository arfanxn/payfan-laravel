<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const CREATED_AT = null, UPDATED_AT = null;

    public const STATUS_COMPLETED = "COMPLETED";

    public const TYPE_REQUEST_MONEY =  "REQUEST", TYPE_SEND_MONEY = "SEND", TYPE_REWARD = "REWARD";

    protected $fillable = [
        "tx_hash", "from_wallet", "to_wallet", "status", "type", "note",
        "amount", "charge", "started_at", "completed_at"
    ];
}
