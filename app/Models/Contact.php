<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    public const UPDATED_AT = null, CREATED_AT = null;

    public const STATUS_ADDED = "ADDED", STATUS_BLOCKED = "BLOCKED";

    protected $fillable = [
        "owner_id", "saved_id", "status",
        "total_transaction", 'last_transaction', 'added_at'
    ];
}
