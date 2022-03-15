<?php

namespace App\Http\Controllers;

use App\Actions\SendMoneyAction;
use App\Models\Transaction;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SendMoneyController extends Controller
{
    public function __invoke(Request $request, SendMoneyAction $sendMoney)
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

        try {
            $sendMoneyData = $sendMoney->setFromWallet(Auth::user()->wallet)->setToWallet($toWalletAddress)
                ->setAmount($amount)->setCharge($charge)->setNote($note)->exec();

            return $sendMoneyData ?
                response()->json(['message' => "Send money successfully.", "invoice" => $sendMoneyData])
                : ErrorsResponse::server();
        } catch (\Exception $e) {
            return response($e);
            return ErrorsResponse::server();
        }
    }
}
