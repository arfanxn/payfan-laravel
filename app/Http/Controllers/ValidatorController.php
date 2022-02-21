<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            "email" => ["required", "email",  Rule::unique('users', 'email')
                ->ignore(Auth::id())]
        ]);
        return $validator->fails() ? response()->json(
            [$validator->errors()->messages(), "valid" => false],
            422
        ) : $this->responseValid();
    }

    public function passwordCheck(Request $request)
    {
        $validator = Validator::make($request->only(["password"]), [
            "password" => "required|string"
        ]);

        if ($validator->fails())
            return response()->json(
                [$validator->errors()->messages(), "valid" => false],
                422
            );

        if (Hash::check(
            $validator->validated()["password"] ?? null,
            Auth::user()->password,
        )) return  $this->responseValid();

        return  $this->responseValid(false)->setStatusCode(403);
    }
}
