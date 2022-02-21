<?php

namespace App\Responses;

class ErrorsResponse
{
    public static function server()
    {
        return response()->json(["error_message" => "Something went wrong."], 500);
    }
}
