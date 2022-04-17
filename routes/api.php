<?php

use App\Http\Controllers\__TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RequestPaymentController;
use App\Http\Controllers\SearchPeopleController;
use App\Http\Controllers\SendPaymentController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

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

Route::get("users-and-contacts/search", [SearchPeopleController::class, "searchExceptSelf"])->middleware("auth");
Route::prefix("users")->group(function () {
    Route::post('/password/recovery', [AuthController::class, 'passwordRecovery'])
        ->middleware(['vc_or_sq.verify']);

    Route::post('/register', [AuthController::class, 'register'])->middleware("verification_code.verify");
    Route::post('/login', [AuthController::class, 'login'])->name("login");

    Route::prefix("verification-code")->group(function () {
        Route::post("/create", [AuthController::class, "createVerificationCode"])
            ->middleware(["throttle:one_perminute"]);
        Route::post("/verify", [AuthController::class, "verifyVerificationCode"]);
    });
});
Route::prefix("user")->middleware("api")->group(function () {
    Route::get("{userIDorEmail}/settings", [UserSettingController::class, "get"]);

    Route::middleware("auth")->group(function () {
        Route::get('/logout', [AuthController::class, 'logout']);

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
                Route::get("", [ContactController::class,  'index']);
            });
            Route::prefix("contact")->group(function () {
                Route::get("{contact:id}/last-transaction", [ContactController::class, "lastTransactionDetails"]);
                Route::get("{contact:id}/toggle-favorite", [ContactController::class, "toggleFavorite"]);
                Route::post("/add-or-rm/user/{user:id}", [ContactController::class, "addOrRemove"]);
                Route::patch("/{contact:id}/block", [ContactController::class, "block"]);
                Route::patch("/{contact:id}/unblock", [ContactController::class, "unblock"]);
            });

            Route::get("payments", [PaymentController::class, "index"]);
            Route::get("payment/{payment:id}", [PaymentController::class, "show"]);
            Route::prefix("transaction")->group(function () {
                Route::post("/send-payment", SendPaymentController::class)->middleware("verification_code.verify");
                Route::post("/request-payment/make", [RequestPaymentController::class, "make"]);
                Route::patch(
                    "/request-payment/payment/{payment:id}/approve",
                    [RequestPaymentController::class, "approve"]
                )->middleware("verification_code.verify");
                Route::patch("/request-payment/payment/{payment:id}/reject", [RequestPaymentController::class, "reject"]);
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



Route::prefix("test")->group(function () {
    Route::get("", [__TestController::class, "index"]);
    Route::get("preview/mail-notification/", [__TestController::class, "previewMailNotification"]);
});
