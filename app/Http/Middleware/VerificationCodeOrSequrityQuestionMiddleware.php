<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Responses\VerificationCodeResponse;
use App\Services\VerificationCodeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VerificationCodeOrSequrityQuestionMiddleware
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
            "code" => [Rule::requiredIf(fn () => !$request->get("security_answer", false)), "numeric", "digits:6"],
            "security_answer" => [Rule::requiredIf(fn () => !$request->get("code", false)), "string"],
        ]);
        $middlewareFailStatusText = "VerificationCodeOrSequrityQuestionMiddleware";
        $email = $validator->validated()["email"] ?? Auth::user()->email ?? null;
        $code = $validator->validated()["code"] ?? null;
        $securityAnswer = $validator->validated()["security_answer"] ?? null;

        if ($validator->fails()) return response($validator->errors()->messages())
            ->setStatusCode(422, $middlewareFailStatusText);

        if ($code) {
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
        } else if ($securityAnswer) {
            $user = User::with("settings")->where("email", $email)->first();
            if (/**/(strtolower($user->settings->security_answer) == strtolower($securityAnswer)) /**/) {
                $response = $next($request);
                return $response;
            }

            return response()->json([
                "security_answer_error_message" => "Incorrect security answer",
            ])->setStatusCode(277, $middlewareFailStatusText);
        }
    }
}
