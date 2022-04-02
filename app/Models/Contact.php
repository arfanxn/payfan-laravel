<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    public const UPDATED_AT = null, CREATED_AT = null;

    public const STATUS_ADDED = "ADDED", STATUS_FAVORITED = "FAVORITED",  STATUS_BLOCKED = "BLOCKED";

    protected $fillable = [
        "owner_id", "saved_id", "status",
        "total_transaction", 'last_transaction', 'added_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_transaction' => 'datetime',
        "added_at" => 'datetime',
    ];

    public function getRouteKey()
    {
        return "id";
    }

    public function user()
    {
        return $this->belongsTo(User::class, "saved_id", "id");
    }
}
