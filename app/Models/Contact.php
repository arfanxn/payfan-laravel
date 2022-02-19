<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        "owner_id", "saved_id", "status",
        "total_transaction", 'last_transaction', 'added_at'
    ];
}
