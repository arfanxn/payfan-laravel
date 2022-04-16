<?php

namespace App\Http\Controllers;

use App\Actions\RequestPaymentAction;
use App\Models\Payment;
use App\Models\Transaction;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class RequestPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function make(Request $request, RequestPaymentAction $requestPayment)
    {
        $validator = Validator::make($request->all(), [
            "note" => "nullable|string|max:255",
            "amount" => "required|numeric|between:" . Transaction::MINIMUM_AMOUNT . ","  . Transaction::MAXIMUM_AMOUNT,
            "to_wallet" => "required|min:16",
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $amount = floatval($validator->validated()['amount']);
        $note = $validator->validated()['note']  ?? "";
        $toWalletAddress = $validator->validated()['to_wallet'];

        $requestPaymentData = $requestPayment->setFromWallet(Auth::user()->wallet)->setToWallet($toWalletAddress)
            ->setAmount($amount)->setNote($note)->make();

        // if the operations was failed
        if ($requestPaymentData instanceof \Exception) {
            if ($requestPaymentData instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $requestPaymentData->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $requestPaymentData->getMessage()], 500)
                : ErrorsResponse::server();
        }

        return $requestPaymentData ?
            response()->json([
                'message' => "Requesting Payment or Payment to Wallet address : $toWalletAddress, has been sent successfully.",
                "invoice" => $requestPaymentData
            ])
            : ErrorsResponse::server();
    }

    public function approve(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            "charge" => "nullable|numeric|between:0.00,100.00"
        ]);
        if ($validator->fails()) return response()->json($validator->errors()->messages(), 422);

        $charge = floatval($validator->validated()['charge'] ?? 0);

        $approvedReqPaymentData = RequestPaymentAction::approve($payment, $charge);

        // if the operations was failed
        if ($approvedReqPaymentData instanceof \Exception) {
            if ($approvedReqPaymentData instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $approvedReqPaymentData->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $approvedReqPaymentData->getMessage()], 500)
                : ErrorsResponse::server();
        }

        return $approvedReqPaymentData ?
            response()->json([
                'message' => "Requested Payment or Payment has been approved successfully.",
                "invoice" => $approvedReqPaymentData
            ])
            : ErrorsResponse::server();
    }

    public function reject(Request $request, Payment $payment)
    {
        $rejectedReqPaymentData = RequestPaymentAction::reject($payment);

        // if the operations was failed
        if ($rejectedReqPaymentData instanceof \Exception) {
            if ($rejectedReqPaymentData instanceof \App\Exceptions\TransactionException)
                return response()->json(['error_message' => $rejectedReqPaymentData->getMessage()], 300);

            return app()->environment(['local', "debug", "debugging"]) ?
                response()->json(['error_message' => $rejectedReqPaymentData->getMessage()], 500)
                : ErrorsResponse::server();
        }

        return $rejectedReqPaymentData ?
            response()->json([
                'message' => "Requested Payment or Payment has been rejected successfully.",
                "invoice" => $rejectedReqPaymentData
            ])
            : ErrorsResponse::server();
    }
}
