<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SearchPeopleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\ValidatorController;

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
                Route::patch("notifications", [UserSettingController::class, "updateNotificationSettings"]);
            });

            Route::prefix("contacts")->group(function () {
                Route::get("top-contacts", [ContactController::class,  'topContacts']);
                Route::get("{contact:savedContactID}/last-transaction-with", [ContactController::class, "lastTransactionWithContact"]);
            });
        });
    });
});

Route::get("users-and-contacts/search", [SearchPeopleController::class, "searchExceptSelf"])->middleware("auth");



Route::post("test", function (Request  $request) {
});
