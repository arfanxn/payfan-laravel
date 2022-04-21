<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\Transactions\SendMoneyNotification;
use App\Repositories\ContactRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NumberFormatter;

class __TestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // do your own test 
        return dd("success");
    }

    public function previewNotification(Request $request)
    {
        $user = \App\Models\User::offset(1)->limit(1)->first();
        $paymentSendMoney = \App\Models\Payment::query()->where(
            fn ($q) =>
            $q->where("type", \App\Models\Payment::TYPE_SENDING)
                ->where("status", "COMPLETED")
        )->orderBy("started_at", "desc")->first();

        $notification = (new \App\Notifications\VerificationCodeNotification(112233))->toMail($user);
        return $notification->render();
    }

    public function rabbitMQ(Request $request)
    {
        Notification::route("mail", "arf@gm.com")
            ->notify(
                new \App\Notifications\VerificationCodeNotification("002233", ['notifiable_name' => "Muhammad Arfan"])
            );
        return dd(now());
    }
}
