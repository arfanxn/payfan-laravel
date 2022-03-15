<?php

namespace App\Http\Controllers;

use App\Actions\RequestMoneyAction;
use App\Models\Transaction;
use App\Notifications\MakeRequestMoneyNotification;
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

        try {
            $requestMoneyData = $requestMoney->setFromWallet(Auth::user()->wallet)->setToWallet($toWalletAddress)
                ->setAmount($amount)->setNote($note)->make();

            Notification::send(Auth::user(), new MakeRequestMoneyNotification($requestMoneyData));

            return $requestMoneyData ?
                response()->json(['message' => "Send money successfully.", "invoice" => $requestMoneyData])
                : ErrorsResponse::server();
        } catch (\Exception $e) {
            return response($e);
            return ErrorsResponse::server();
        }
    }

    public function approve(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            "charge" => "nullable|numeric|between:0.00,100.00"
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $charge = floatval($validator->validated()['charge']) ?? 0;

        try {
            $approvedReqMoney = RequestMoneyAction::approve($transaction, $charge);

            return $approvedReqMoney ?
                response()->json([
                    'message' => "Send money successfully.",
                    "invoice" => $approvedReqMoney
                ])
                : ErrorsResponse::server();
        } catch (\Exception $e) {
            return response($e);
            return ErrorsResponse::server();
        }
    }
}
