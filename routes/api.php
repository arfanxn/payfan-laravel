<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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
            Route::post("/is-taken/except/self", [ValidatorController::class, "isEmailTakenExceptSelf"]);
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

        Route::put("/self", [UserController::class, "update"]);
        Route::patch("/email/self", [UserController::class, "updateEmail"])->middleware("verification_code.verify");
    });
});



Route::get("test", function () {
    return \App\Responses\VerificationCodeResponse::fail();
});
