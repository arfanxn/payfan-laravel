<?php

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

Broadcast::channel('User.{id}', function ($user, $id) {
    return intval($user->id) === intval($id);
});

Broadcast::channel('Notification.{notifiable_id}', function ($user, $notifiable_id) {
    return intval($user->id) === intval($notifiable_id ?? null);
});
