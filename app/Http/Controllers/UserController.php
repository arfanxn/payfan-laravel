<?php

namespace App\Http\Controllers;

use App\Helpers\StrHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        if ($validator->fails()) return response()->json($validator->errors()->messages())->setStatusCode(422);

        $isUpdateSuccess = User::where("id", Auth::id())->update(["email" => $request->email]);

        return $isUpdateSuccess ? response("Update success")
            : response("Update failed", 500);
    }

    public function updateName(Request $request)
    {
        $validator = Validator::make($request->only(["name"]), [
            "name" => "required|min:2|string|max:100",
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages())->setStatusCode(422);

        $isUpdateSuccess = User::where("id", Auth::id())->update(["name" => $request->name]);

        return $isUpdateSuccess ? response("Update success")
            : response("Update failed", 500);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->only(
            ["current_password", "password", "password_confirmation"],
        ), [
            "current_password" => "required|string",
            "password" => "required|string|min:8",
            "password_confirmation" => "required|string|same:password"
        ]);

        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        if (!Hash::check(
            $validator->validated()["current_password"] ?? null,
            Auth::user()->password,
        )) return response()->json(["error_message" => "Password are incorect!"], 403);

        $isUpdateSuccess = User::where("id", Auth::id())
            ->update(["password" => bcrypt($validator->validated()["password"])]);

        return $isUpdateSuccess ? response()->json("Password updated successfully")
            : response()->json("Update failed")->setStatusCode(500);
    }

    public function updateProfilePict(Request $request)
    {
        $validator = Validator::make($request->only("profile_pict"), [
            "profile_pict" => "required|image|mimes:jpeg,png,jpg,gif,bmp",
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $fileName = StrHelper::make(Auth::user()->name)->removeWhitespace()->toLowerCase()->limit(20)->get() . now()->timestamp . "."  . $request->file("profile_pict")->extension();

        Log::info($request->file("profile_pict"));

        $request->file("profile_pict")->storeAs("public", $fileName);

        User::where("id", Auth::id())->update([
            "profile_pict" =>  $fileName,
        ]);
    }
}
