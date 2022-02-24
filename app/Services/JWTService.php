<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class JWTService
{
    public static function getPayload($jwt = null)
    {
        $jwt = $jwt ?? Cookie::get("jwt");
        $jwtPayload = explode(".", $jwt)[1];
        $jwtPayloadDecoded  = json_decode(base64_decode($jwtPayload));
        return $jwtPayloadDecoded;
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function responseNewToken($token, $message = "")
    {
        return response()->json([
            'access_token' => app()->environment('local') ?  $token : "Application Environment isn't local, the \"access_token\" only sent with Cookies (httpOnly = true), due to secure our clients and data",
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            "message" => $message,
            'user' => app()->environment('local') ?  Auth::user() : "Application Environment isn't local, the \"user\" only sent when Application Environment is set to local, due to secure our clients and data"
        ])->withCookie("jwt", $token, 60 * 24);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function responseRefreshToken($message = "")
    {
        return self::responseNewToken(Auth::refresh(), $message);
    }
}
