<?php

use App\Providers\BroadcastServiceProvider;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*z
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel(BroadcastServiceProvider::USER_AUTHORIZATION() . "{id}", function ($user, $id) {
    return intval($user->id) === intval($id);
});

Broadcast::channel('wallets.{id}', function ($user, $id) {
    $wallet = \App\Models\Wallet::query()->where("id", $id)->first();
    return intval($user->id) === intval($wallet->user_id ?? null);
});

Broadcast::channel('payments.{uuid}', function ($user, $uuid) {
    $payment = \App\Models\Order::query()->where("id", $uuid)->first();
    return intval($user->id) === intval($payment->user_id ?? null);
});
