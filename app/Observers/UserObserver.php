<?php

namespace App\Observers;

use App\Helpers\StrHelper;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Wallet;

class UserObserver
{
    public function created(User $user)
    {
        Wallet::create([
            "user_id" => $user->id,
            "address" => StrHelper::make(StrHelper::random(16))->toUpperCase()->get(),
            "balance" => 0.00,
            "total_transaction" => 0,
            "last_transaction" => null
        ]);
        UserSetting::create([
            "user_id" => $user->id,
            "two_factor_auth" => false,
            "security_question" => null, "security_answer" => null,
            "payment_notification" => 1,
            "request_notification" => 1,
            "receive_notification" => 1,
            'updated_at' => null,
        ]);
    }
}
