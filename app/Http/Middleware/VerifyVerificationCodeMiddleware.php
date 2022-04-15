<?php

namespace App\Http\Middleware;

use App\Responses\VerificationCodeResponse;
use App\Services\VerificationCodeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            "email" =>  [Rule::requiredIf(fn () =>  !Auth::check()), "email"],
            "code" => "required|numeric|digits:6"
        ]);
        $middlewareFailStatusText = "VerifyVerificationCodeMiddleware";
        /*  read for understanding "email" variable
            "email" is required if user is not logged in 

            if user is already logged in and "email" is not provided then get the email from the logged in user (Auth::user()->email)

            so "email" is only required if user is not logged in ,
                and if user is logged in "email" is optional you can provide it or not

            if user is already logged in and "email" is provided then use the provided "email" instead of from (Auth::user()->email)
            */
        $email = $validator->validated()["email"] ?? Auth::user()->email ?? null;
        // end 

        $code = $validator->validated()["code"] ?? null;

        if ($validator->fails()) return response($validator->errors()->messages())
            ->setStatusCode(422, $middlewareFailStatusText);

        $hashedCode = VerificationCodeService::getHashedCode($request);

        if (!$hashedCode) return VerificationCodeResponse::hashedCodeNotAvailable()
            ->setStatusCode(422, $middlewareFailStatusText);

        $isVerified = VerificationCodeService::verify($email, $code, $hashedCode);

        if ($isVerified) {
            $response = $next($request);
            return $response->withCookie(cookie("hashed_code", null, 1)); // return  response and remove cookie "hashed_code" 
        }

        return VerificationCodeResponse::fail()
            ->setStatusCode(277, $middlewareFailStatusText);
    }
}
