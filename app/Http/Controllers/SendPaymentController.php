<?php

namespace App\Http\Controllers;

use App\Actions\SendPaymentAction;
use App\Models\Transaction;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SendPaymentController extends Controller
{
    public function __invoke(Request $request, SendPaymentAction $sendPayment)
    {
        $validator = Validator::make($request->all(), [
            "note" => "nullable|string|max:255",
            "amount" => "required|numeric|between:" . Transaction::MINIMUM_AMOUNT . ","  . Transaction::MAXIMUM_AMOUNT,
            "charge" => "nullable|numeric",
            "to_wallet" => "required|min:16",
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $amount = floatval($validator->validated()['amount']);
        $charge = $validator->validated()['charge'] ?? 0;
        $note = $validator->validated()['note']  ?? "";
        $toWalletAddress = $validator->validated()['to_wallet'];

        $sendPaymentData = $sendPayment->setFromWallet(Auth::user()->wallet)->setToWallet($toWalletAddress)
            ->setAmount($amount)->setCharge($charge)->setNote($note)->exec();

        if ($sendPaymentData instanceof \Exception) {
            if ($sendPaymentData instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $sendPaymentData->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $sendPaymentData->getMessage()], 500)
                : ErrorsResponse::server();
        }

        return $sendPaymentData ?
            response()->json(['message' => "Send payment successfully.", "invoice" => $sendPaymentData])
            : ErrorsResponse::server();
    }
}
