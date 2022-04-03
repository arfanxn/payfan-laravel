<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RequestMoneyController;
use App\Http\Controllers\SearchPeopleController;
use App\Http\Controllers\SendMoneyController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\ValidatorController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix("validator")->group(function () {
    Route::prefix("users")->group(function () {
        Route::prefix("email")->group(function () {
            Route::post("/is-taken", [ValidatorController::class, "isEmailTaken"]);
            Route::post("/is-taken/except/self", [
                ValidatorController::class,
                "isEmailTakenExceptSelf"
            ])->middleware("auth");
        });
    });

    Route::prefix("user")->group(function () {
        Route::middleware("auth")->group(function () {
            Route::prefix("password")->group(function () {
                Route::post("/check/self", [ValidatorController::class, "passwordCheck"])
                    ->middleware("auth");
            });
        });
    });
});

Route::prefix("user")->middleware("api")->group(function () {

    Route::post('/register', [AuthController::class, 'register'])->middleware("verification_code.verify");
    Route::post('/login', [AuthController::class, 'login'])->name("login");

    Route::prefix("verification-code")->group(function () {
        Route::post("/create", [AuthController::class, "createVerificationCode"]) // ;
            ->middleware(["throttle:one_perminute"]);
        Route::post("/verify", [AuthController::class, "verifyVerificationCode"]);
    });

    Route::middleware("auth")->group(function () {
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'userProfile']);

        Route::prefix("auth")->group(function () {
            Route::get('/refresh', [AuthController::class, 'refresh']);
            Route::get("/check", [AuthController::class, 'check']);
        });

        Route::prefix("self")->group(function () {
            Route::get("", [UserController::class, "self"]);
            Route::put("", [UserController::class, "update"]);
            Route::patch("/name", [UserController::class, "updateName"]);
            Route::post("/profile-pict", [UserController::class, "updateProfilePict"]);
            Route::middleware("verification_code.verify")->group(function () {
                Route::patch("/email", [UserController::class, "updateEmail"]);
                Route::patch("/password", [UserController::class, "updatePassword"]);
            });

            Route::prefix("settings")->middleware("verification_code.verify")->group(function () {
                Route::patch("/2fa", [UserSettingController::class, "disableOrEnable2FA"]);
                Route::patch("security-question", [UserSettingController::class, "updateSecurityQuestion"]);
                Route::patch("notifications", [UserSettingController::class, "updateNotificationSettings"])
                    ->withoutMiddleware(["verification_code.verify"]);
            });

            Route::prefix("contacts")->group(function () {
                Route::get("top-contacts", [ContactController::class,  'topContacts']);
            });
            Route::prefix("contact")->group(function () {
                Route::get("{contact}/last-transaction", [ContactController::class, "lastTransactionDetail"]);
                Route::get("{contact}/toggle-favorite", [ContactController::class, "toggleFavorite"]);
                Route::post("/add-or-rm/user/{user:id}", [ContactController::class, "addOrRemove"]);
                Route::post("/{contact}/block", [ContactController::class, "block"]);
            });

            Route::get("orders", [OrderController::class, "index"]);
            Route::get("order/{order:id}", [OrderController::class, "show"]);
            Route::prefix("transaction")->group(function () {
                Route::post("/send-money", SendMoneyController::class)->middleware("verification_code.verify");
                Route::post("/request-money/make", [RequestMoneyController::class, "make"]);
                Route::patch(
                    "/request-money/order/{order:id}/approve",
                    [RequestMoneyController::class, "approve"]
                )->middleware("verification_code.verify");
                Route::patch("/request-money/order/{order:id}/reject", [RequestMoneyController::class, "reject"]);
            });

            Route::prefix("notifications")->group(function () { // pluralize
                Route::get("", [NotificationController::class, "index"]);
                Route::delete("readed", [NotificationController::class, "deleteReaded"]);;
            });
            Route::prefix("notification")->group(function () { // singularize
                Route::get("{id}", [NotificationController::class, "show"]);
                Route::post("{id}/mark-as-read", [NotificationController::class, "markAsRead"]);
                Route::post("{id}/mark-as-unread", [NotificationController::class, "markAsUnread"]);
            });
        });
    });
});

Route::get("users-and-contacts/search", [SearchPeopleController::class, "searchExceptSelf"])->middleware("auth");



Route::prefix("test")->group(function () {
    Route::get("", function (Request  $request) {
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
    });
    Route::get("preview/mail-notification/", function (Request  $request) {
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
    });
});
