<?php

namespace App\Http\Controllers;

use App\Actions\RequestMoneyAction;
use App\Models\Order;
use App\Models\Transaction;
use App\Notifications\MakeRequestingMoneyNotification;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class RequestMoneyController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function make(Request $request, RequestMoneyAction $requestMoney)
    {
        $validator = Validator::make($request->all(), [
            "note" => "nullable|string|max:255",
            "amount" => "required|numeric|between:" . Transaction::MINIMUM_AMOUNT . ","  . Transaction::MAXIMUM_AMOUNT,
            "to_wallet" => "required|min:16",
        ]);

        $amount = floatval($validator->validated()['amount']);
        $note = $validator->validated()['note']  ?? "";
        $toWalletAddress = $validator->validated()['to_wallet'];

        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $requestMoneyData = $requestMoney->setFromWallet(Auth::user()->wallet)->setToWallet($toWalletAddress)
            ->setAmount($amount)->setNote($note)->make();

        // if the operations was failed
        if ($requestMoneyData instanceof \Exception) {
            if ($requestMoneyData instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $requestMoneyData->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $requestMoneyData->getMessage()], 500)
                : ErrorsResponse::server();
        }

        Notification::send(Auth::user(), new MakeRequestingMoneyNotification($requestMoneyData));

        return $requestMoneyData ?
            response()->json([
                'message' => "Requesting Money or Payment to Wallet address : $toWalletAddress, has been sent successfully.",
                "invoice" => $requestMoneyData
            ])
            : ErrorsResponse::server();
    }

    public function approve(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            "charge" => "nullable|numeric|between:0.00,100.00"
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $charge = floatval($validator->validated()['charge']) ?? 0;


        $approvedReqMoney = RequestMoneyAction::approve($order, $charge);

        // if the operations was failed
        if ($approvedReqMoney instanceof \Exception) {
            if ($approvedReqMoney instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $approvedReqMoney->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $approvedReqMoney->getMessage()], 500)
                : ErrorsResponse::server();
        }

        return $approvedReqMoney ?
            response()->json([
                'message' => "Requested Money or Payment has been approved.",
                "invoice" => $approvedReqMoney
            ])
            : ErrorsResponse::server();
    }
}
