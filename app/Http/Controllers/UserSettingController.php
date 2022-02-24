<?php

namespace App\Http\Controllers;

use App\Helpers\StrHelper;
use App\Models\User;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserSettingController extends Controller
{
    public function disableOrEnable2FA( /*Request $request */)
    {
        // $validator =  Validator::make($request->only("2fa"), [
        //     "2fA" => "required|boolean"
        // ]);
        // if ($validator->fails()) return  response()->json($validator->errors()->messages())->setStatusCode(422);
        $userSetting = Auth::user()->settings;
        $update2FA =  $userSetting->disableOrEnable2FA();

        return $update2FA  ? response()->json([
            "message" => "2FA updated",
            "2fa" => "isEnabled == " . boolval($userSetting->isRequire2FA()) ?: "false"
        ]) : ErrorsResponse::server();
    }

    public function updateSecurityQuestion(Request $request)
    {
        $validator = Validator::make($request->only(["security_question", "security_answer"]), [
            "security_question" => "required|string|min:8|max:50",
            "security_answer" => "required|string|min:8|max:50"
        ]);
        if ($validator->fails()) return  response()->json($validator->errors()->messages())->setStatusCode(422);

        $updateSQ = User::where("id", Auth::id())->first()->settings()->update([
            "security_question" =>
            StrHelper::make($validator->validated()["security_question"])->toLowerCase()->result(),
            "security_answer" =>
            StrHelper::make($validator->validated()["security_answer"])->toLowerCase()->result(),
        ]);

        return $updateSQ ?
            response()->json(["message" => "Security question update successfully."]) : ErrorsResponse::server();
    }

    public function updateNotificationSettings(Request $request)
    {
        $validator = Validator::make($request->only(["security_question", "security_answer"]), [
            "request_notification" => "nullable|boolen",
            "receive_notification" => "nullable|boolen"
        ]);
        if ($validator->fails()) return  response()->json($validator->errors()->messages())->setStatusCode(422);

        $userSetting = Auth::user()->settings;

        $messages  = [];

        // if ($request->has("payment_notification")) // disbaled feature 
        //     $userSetting->payment_notification =  boolval($request->payment_notification) ? 1 : 0;
        if ($request->has("request_notification")) {
            $userSetting->request_notification = boolval($request->request_notification) ? 1 : 0;
            array_merge($messages, ["request_notification" => "Request notification updated successfully"]);
        }
        if ($request->has("receive_notification")) {
            $userSetting->receive_notification = boolval($request->receive_notification) ? 1 : 0;
            array_merge($messages, ["receive_notification" => "Receive notification updated successfully"]);
        }

        $updateNotificationSettings = $userSetting->save();

        return $updateNotificationSettings ?
            response()->json(["message" => $messages]) : ErrorsResponse::server();
    }
}
