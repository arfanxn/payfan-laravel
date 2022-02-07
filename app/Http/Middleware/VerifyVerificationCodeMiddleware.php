<?php

namespace App\Http\Middleware;

use App\Responses\VerificationCodeResponse;
use App\Services\VerificationCodeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VerifyVerificationCodeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
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

        if (!$hashedCode) return VerificationCodeResponse::hashedCodeNotAvailable();

        $isVerified = VerificationCodeService::verify($email, $code, $hashedCode);

        if ($isVerified) {
            $response = $next($request);
            return $response->withCookie(cookie("hashed_code", null, 1));
        }

        return VerificationCodeResponse::fail()->header("From-Middleware", __CLASS__);
    }
}
