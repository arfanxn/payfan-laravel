<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    const CREATED_AT = null;

    protected $fillable = [
        "user_id", "two_factor_auth", "security_question", "security_answer",
        "payment_notification", "request_notification", "receive_notification", 'updated_at'
    ];
}
