<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $user = User::where("id", Auth::id());

        if ($request->has("name")) {
            $validator = Validator::make($request->only(["name"]), [
                "name" => "required|min:2|string|max:100",
            ]);
            if ($validator->fails()) return response()->json($validator->errors()->messages());

            $user->name = $request->name;
        }

        $isUpdateSuccess = $user->save();

        return $isUpdateSuccess ? response("Update success")
            : response("Update fails", 500);
    }

    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->only(["email"]), [
            "email" => "required|email|unique:users,email," . Auth::id(),
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages());

        $isUpdateSuccess = User::where("id", Auth::id())->update(["email" => $request->email]);

        return $isUpdateSuccess ? response("Update success")
            : response("Update failed", 500);
    }

    // public function updateName(Request $request)
    // {
    //     $validator = Validator::make($request->only(["name"]), [
    //         "name" => "required|min:2|string|max:100",
    //     ]);
    //     if ($validator->fails()) return response()->json($validator->errors()->messages());

    //     $isUpdateSuccess = User::where("id", Auth::id())->update(["name" => $request->name]);

    //     return $isUpdateSuccess ? response("Update success")
    //         : response("Update failed", 500);
    // }
}
