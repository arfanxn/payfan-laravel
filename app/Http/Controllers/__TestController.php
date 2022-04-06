<?php

namespace App\Http\Controllers;

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
        $user = \App\Models\User::offset(1)->limit(1)->first();
        $orderSendMoney = \App\Models\Order::query()->where(
            fn ($q) =>
            $q->where("type", \App\Models\Order::TYPE_SENDING_MONEY)
                ->where("status", "COMPLETED")
        )->orderBy("started_at", "desc")->first();

        $isSent = \Illuminate\Support\Facades\Notification::send(
            $user,
            new \App\Notifications\Transactions\SendMoneyNotification($orderSendMoney)
        );

        return dd($isSent);
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

    public function triggerEvent()
    {
        $event = event(new \App\Events\NewNotificationEvent(
            NotificationRepository::make()->getBuilder()
                ->where("notifiable_id", 2)->orderBy('created_at', 'DESC')->first(),
        )/**/);

        return response()->json(
            [
                $event,
                "message" => "Event has been sent!"
            ],
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
