<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public const TYPE_SEND_MONEY = "SEND", TYPE_REQ_MONEY = "REQUEST", TYPE_RECEIVE = "RECEIVE";
    public const STATUS_UNREAD = "UNREAD", STATUS_READED = "READED";

    protected $fillable = ["user_id", "type", "title", "redirect", "body", "status"];
}
