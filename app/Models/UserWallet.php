<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    use HasFactory;

    const CREATED_AT = null, UPDATED_AT = null;

    protected  $fillable =  [
        "user_id", "address", "balance", "total_transaction", "last_transaction"
    ];
}
