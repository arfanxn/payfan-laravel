<?php

namespace App\Services;

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
}
