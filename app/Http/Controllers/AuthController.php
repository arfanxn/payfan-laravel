<?php

namespace App\Http\Controllers;

use App\Helpers\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use App\Responses\VerificationCodeResponse;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\JWTService;
use Illuminate\Support\Facades\Notification;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        return auth()->shouldUse('api');
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            "code" => "nullable|string|digits:6"
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        if (!$token = Auth::attempt($request->only(['email', 'password'])) /**/) {
            return response()->json(["error_message" => "Your credentials doesn't match our records"], 401);
        }

        $isRequire2FA = Auth::user()->settings->isRequire2FA();
        if ($isRequire2FA) {
            if (!isset($validator->validated()["code"]) || !$validator->validated()["code"])
                return response()
                    ->json(["error_message" => "Two factor authentication enabled, Verification code required."])
                    ->setStatusCode(422, 'Require2FA');

            $hashedCode = VerificationCodeService::getHashedCode($request);
            if (!$hashedCode)
                return VerificationCodeResponse::hashedCodeNotAvailable();


            $isVerified =  VerificationCodeService::verify(
                $validator->validated()["email"],
                $validator->validated()["code"],
                $hashedCode
            );
            return $isVerified ? VerificationCodeResponse::successAndLogin($token)
                : VerificationCodeResponse::fail()->setStatusCode(277, "VerifyVerificationCodeMiddleware");
        } else {
            return JWTService::responseNewToken($token, "Successfully logged in.");
        }
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            "password_confirmation" => "required|string|min:8|same:password"
        ]);

        if ($validator->fails()) return response()->json($validator->errors()->messages(), 400);

        $user = User::create(array_merge(
            $validator->validated(),
            [
                "name" => ucwords(strtolower($request->name)),
                "email_verified_at" => now()->toDateTimeString(), // email verificaion process are handled behind the scene at the "VerifyVerificationCodeMiddleware"   
                'password' => bcrypt($request->password),
                "profile_pict" => '#' . StrHelper::random(6, "ABCDEF0123456789", true)->toUpperCase()->get()
            ]
        ));

        $token = Auth::attempt([
            "email" => $validator->validated()["email"],
            "password" => $validator->validated()["password"]
        ]);

        return response()->json([
            'message' => 'Successfully registered, please login.',
            'user' => $user
        ], 201)->withCookie("jwt", $token, 60 * 24);
    }

    /**
     * password recovery.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function passwordRecovery(Request $request)
    {
        $validator = Validator::make(
            $request->only(["email", "new_password", "new_password_confirmation"]),
            [   // rules
                "email" => "email|required|max:100",
                "new_password" => "required|string|max:100|min:8",
                "new_password_confirmation" => "required|string|max:100|min:8|same:new_password",
            ]
        );

        if (
            $validator->validated()['new_password']
            != $validator->validated()["new_password_confirmation"]
        ) return response()->json(['error_message' => 'Password not match!']);

        $isUpdateSuccess = User::where("email", $validator->validated()['email'])
            ->update([
                "password" => bcrypt($validator->validated()['new_password']),
            ]);

        return $isUpdateSuccess ? response()->json("Password reseted successfully")
            : response()->json("Update failed")->setStatusCode(500);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'User successfully signed out'])
            ->withCookie(cookie("jwt", null, 60));
    }

    /**
     * check is a user auth or not. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        return response(["authenticated" => Auth::check()]);
    }

    public function createVerificationCode(Request $request)
    {
        $validator = Validator::make($request->only(["email", "reason"]), [
            "email" =>  [Rule::requiredIf(!Cookie::has("jwt"))],
            "reason" => "nullable|string|max:100",
            "notifiable_name" => "nullable|string|max:50"
        ]);
        if ($validator->fails()) return response($validator->errors()->messages(), 422);

        $email = $validator->validated()['email'] ?? null;
        $verificationReason = $validator->validated()['reason'] ?? null;
        $verificationNotifiableName = $validator->validated()['notifiable_name'] ?? null;

        if (Cookie::has("jwt") && !$email) { // if the user has jwt (logged in) && "email" is null/falsy
            $jwtPayl = JWTService::getPayload();
            $user = User::where("id", $jwtPayl->sub)->first();
            $email = $user->email;

            $verificationCode = VerificationCodeService::generate(6);
            $hashedCode = VerificationCodeService::createHash($email, $verificationCode, 30);

            Notification::send($user, new VerificationCodeNotification($verificationCode, [
                "reason" => $verificationReason
            ]));

            return response()->json()->withCookie(cookie("hashed_code", $hashedCode, 30));
        } else if ($email) { // if email is provided , whatever either user is logged in or not
            $user = User::where("email", $email /**/)->first();

            $verificationCode = VerificationCodeService::generate(6);
            $hashedCode = VerificationCodeService::createHash($email, $verificationCode, 30);

            $user ? // if "user" found send the verification code notification with "user" data/object 
                Notification::send(
                    $user,
                    new VerificationCodeNotification($verificationCode, [
                        'reason' => $verificationReason, "notifiable_name" => $verificationNotifiableName
                    ])
                ) : // if "user" not found send the verification code notification anonymously
                Notification::route("mail", $email/**/)->notify(
                    new VerificationCodeNotification($verificationCode, [
                        'reason' => $verificationReason, "notifiable_name" => $verificationNotifiableName
                    ])
                );

            return response()->json()->withCookie(cookie("hashed_code", $hashedCode, 30));
        }
    }


    public function verifyVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" =>  [Rule::requiredIf(function () {
                return !Auth::check();
            }), "email"], "code" => "required|numeric|digits:6"
        ]);
        $email = $validator->validated()["email"] ?? Auth::user()->email ?? null;
        $code = $validator->validated()["code"] ?? null;

        if ($validator->fails()) return response($validator->errors()->messages(), 422);

        $hashedCode = VerificationCodeService::getHashedCode($request);

        if (!$hashedCode) return  VerificationCodeResponse::hashedCodeNotAvailable();

        $isVerified = VerificationCodeService::verify($email, $code, $hashedCode);

        return $isVerified ?
            VerificationCodeResponse::success()
            : VerificationCodeResponse::fail();
    }
}
