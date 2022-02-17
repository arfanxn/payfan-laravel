<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ValidatorController extends Controller
{
    private function responseValid(bool $isValid = true): JsonResponse
    {
        return response()->json(["valid" => $isValid]);
    }

    public function isEmailTaken(Request $request)
    {
        $validator = Validator::make($request->only("email"), [
            "email" => "required|email|unique:users,email"
        ]);
        return $validator->fails() ? response()->json(
            [$validator->errors()->messages(), "valid" => false],
            422
        ) : $this->responseValid();
    }

    public function isEmailTakenExceptSelf(Request $request)
    {
        $validator = Validator::make($request->only("email"), [
            "email" => "required|email|unique:users,email," . Auth::id()
        ]);
        return $validator->fails() ? response()->json(
            [$validator->errors()->messages(), "valid" => false],
            422
        ) : $this->responseValid();
    }
}
