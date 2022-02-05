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
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->messages(), 422);
        }

        if (!$token = Auth::attempt($validator->validated())) {
            return response()->json(["password" => "Your credentials doesnt match our records",  'error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
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
            'password' => 'required|string|min:6',
            "password_confirmation" => "required|string|min:6|same:password"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->messages(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
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
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(Auth::refresh());
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

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => app()->environment('local') ?  $token : "Application Environment isn't local, the \"access_token\" only sent with Cookies (httpOnly = true), due to secure our clients and data",
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()
        ])->withCookie("jwt", $token, 60 * 24);
    }

    public function createVerificationCode(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            "email" => "email"
        ]);
        $email = $validator->validated()["email"] ?? Auth::user()->email ?? null;

        if ($validator->fails()) return response($validator->errors()->messages(), 422);

        $verificationCode = VerificationCodeService::generate(6);
        $hashedCode = VerificationCodeService::createHash($email, $verificationCode, 30);

        Log::info("verification_code for email : $email is $verificationCode");

        return response(["code" => $verificationCode, "hashed_code" => $hashedCode])
            ->withCookie("hashed_code", $hashedCode, 30);
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
