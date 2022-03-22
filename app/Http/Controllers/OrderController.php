<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\TransactionCollection;
use App\Models\Transaction;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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

        $orderQuery = Order::with(['fromWallet.user', "toWallet.user",])
            ->where(fn ($q) => $q->where("user_id", Auth::id() /**/) /**/);

        $orders = OrderRepository::filters([
            "keyword" => $validatorValidated['keyword'] ?? null,
            "start_at" => $validatorValidated['start_at'] ?? null,
            "end_at" => $validatorValidated['end_at'] ?? null,
            "status" => $validatorValidated['status'] ?? null,
            "type" => $validatorValidated['type'] ?? null,
        ], $orderQuery)->orderBy("started_at", "desc")->simplePaginate(10);

        $orders = new OrderCollection($orders);

        return  response()->json(['orders' => $orders]);
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
    public function show(Order $order)
    {
        if (Gate::denies("has-order", $order)) return response("Forbidden", 403);
        return response()->json(["order" => $order]);
    }
}
