<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Responses\VerificationCodeResponse;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\JWTService;

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
        if ($validator->fails()) {
            return response()->json($validator->errors()->messages(), 422);
        }

        if (!$token = Auth::attempt([
            "email" => $validator->validated()["email"],
            "password" => $validator->validated()["password"]
        ])) {
            return response()->json(["password" => "Your credentials doesn't match our records",  'error_message' => 'Unauthorized'], 401);
        }

        $isRequire2FA = Auth::user()->settings->isRequire2FA();
        if ($isRequire2FA) {
            if (!isset($validator->validated()["code"]) || !$validator->validated()["code"])
                return response()
                    ->json(["error_message" => "Verification Code required to verify it's your"])
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
                : VerificationCodeResponse::fail()->setStatusCode(401, "VerifyVerificationCodeMiddleware");
        } else {
            return JWTService::responseNewToken($token, "Successfully logged in");
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

        if ($validator->fails()) {
            return response()->json($validator->errors()->messages(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            [
                "name" => ucwords($request->name),
                "email_verified_at" => now()->toDateTimeString(),
                'password' => bcrypt($request->password)
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

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(["user" => Auth::user()]);
    }

    public function createVerificationCode(Request $request)
    {
        $validator = Validator::make($request->only("email"), [
            "email" =>  [Rule::requiredIf(!Cookie::has("jwt"))]
        ]);

        if ($validator->fails()) return response($validator->errors()->messages(), 422);

        $email = $validator->validated()["email"] ??  null;

        if (Cookie::has("jwt") && !$validator->validated()["email"]) {
            $jwtPayl = JWTService::getPayload();
            $email = User::where("id", $jwtPayl->sub)
                ->first()->email;
        }

        $verificationCode = VerificationCodeService::generate(6);
        $hashedCode = VerificationCodeService::createHash($email, $verificationCode, 30);

        Log::info("verification_code for email : $email is $verificationCode");

        return response()->json()->withCookie(cookie("hashed_code", $hashedCode, 30));
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
