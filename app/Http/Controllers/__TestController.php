<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\Transactions\SendMoneyNotification;
use App\Repositories\ContactRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class __TestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // return \App\Models\User::limit(1)->first();
        // $user = \App\Models\User::offset(1)->limit(1)->first();
        // $orderSendMoney = \App\Models\Order::query()->where(
        //     fn ($q) =>
        //     $q->where("type", \App\Models\Order::TYPE_SENDING_MONEY)
        //         ->where("status", "COMPLETED")
        // )->orderBy("started_at", "desc")->first();

        // $isSent = \Illuminate\Support\Facades\Notification::send(
        //     $user,
        //     new \App\Notifications\Transactions\SendMoneyNotification($orderSendMoney)
        // );

        // return dd($isSent);
        // ContactRepository::incrementAndUpdate_LastTransactionAndTotalTransaction_whereOwnerIdOrSavedId([4, 5]);

        $notification1 = \App\Models\User::skip(1)->first()->notifications()->first();
        $notification2 = (array) NotificationRepository::make()->where_Notifiable(2)->getBuilder()->first();
        $notification2['data'] = json_decode($notification2['data']);

        return response()->json([$notification1, $notification2]);
    }

    public function previewMailNotification(Request $request)
    {
        $user = \App\Models\User::offset(1)->limit(1)->first();
        $orderSendMoney = \App\Models\Order::query()->where(
            fn ($q) =>
            $q->where("type", \App\Models\Order::TYPE_SENDING_MONEY)
                ->where("status", "COMPLETED")
        )->orderBy("started_at", "desc")->first();

        // $notification = (new \App\Notifications\SendMoneyNotification($orderSendMoney))->toMail($user);
        // return $notification->render();

        $notification = (new \App\Notifications\VerificationCodeNotification(112233, "Login"))->toMail($user);
        return $notification->render();
    }

    public function triggerNotificationBroadcast()
    {
        // $event = event(new \App\Events\NewNotificationEvent(
        //     NotificationRepository::make()->getBuilder()
        //         ->where("notifiable_id", 2)->orderBy('created_at', 'DESC')->first(),
        //     \App\Models\User::skip(1)->first(),
        // )/**/);

        $user = User::query()->where("id", 2)->first();
        $order = Order::query()
            ->where("user_id", $user->id)
            ->where("type", Order::TYPE_SENDING_MONEY)->inRandomOrder(1)->first();

        $user->notify(new SendMoneyNotification($order));

        return response()->json(
            ["message" => "Notification has been sent!", "order" => $order],
        );
    }

    public function sendMoneyNotification()
    {
        Notification::send(
            \App\Models\User::where("id", 2)->first(),
            new \App\Notifications\Transactions\SendMoneyNotification(\App\Models\Order::first())
        );

        return response()->json(
            [
                "message" => "Notification has been sent!",
            ],
        );
    }
}
