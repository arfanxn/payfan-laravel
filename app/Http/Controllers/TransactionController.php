<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionCollection;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "keyword" => "nullable|string",
            "start_at" => "nullable|date|before:end_at",
            "end_at" => "nullable|date|after:start_at",
            "status" => "nullable|string",
            "type" => "nullable|string",
        ]);
        $validatorValidated = $validator->validated();

        $authWallet = Auth::user()->wallet;
        $transactionQuery = Transaction::with(['fromWallet.user', "toWallet.user",])->where(fn ($q)
        => $q->where("from_wallet", $authWallet->id)->orWhere('to_wallet', $authWallet->id));

        $transactions = TransactionRepository::filters([
            "keyword" => $validatorValidated['keyword'] ?? null,
            "start_at" => $validatorValidated['start_at'] ?? null,
            "end_at" => $validatorValidated['end_at'] ?? null,
            "status" => $validatorValidated['status'] ?? null,
            "type" => $validatorValidated['type'] ?? null,
        ], $transactionQuery)->simplePaginate(10);

        $transactions =  new TransactionCollection($transactions);

        return  response()->json(['transactions' => $transactions]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        if (Gate::denies("has-transaction", $transaction)) return response("Forbidden", 403);
        return response()->json(["transaction" => $transaction]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
