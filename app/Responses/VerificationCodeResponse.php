<?php

namespace App\Responses;

use Illuminate\Support\Facades\App;

class VerificationCodeResponse
{
    private const Message = "verification_code_message";
    private const ErrorMessage = "verification_code_error_message";

    public static function success()
    {
        return response([self::Message => "Verified"], 200)
            ->withCookie(cookie("hashed_code", null, 1));
    }

    public static function fail()
    {
        return response([self::ErrorMessage => "OTP or Verification Code are incorect!"], 401);
    }

    public static function hashedCodeNotAvailable()
    {
        return response([self::ErrorMessage =>
        App::environment("local") ?
            "hashed_code not found or not available or not been sent." :
            "Please resend a new Verification Code."], 422);
    }

    public static function codeSended()
    {
        return response([self::Message => "Verification Code has been sent to your email."]);
    }
}
