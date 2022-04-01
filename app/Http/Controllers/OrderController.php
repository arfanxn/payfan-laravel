<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\TransactionCollection;
use App\Models\Transaction;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "keyword" => "nullable|string",
            "start_at" => [
                "nullable", "date",
                Rule::when($request->end_at ?? false, ["before:end_at"])
            ],
            "end_at" => [
                "nullable", "date",
                Rule::when($request->start_at ?? false, ["after:start_at"])
            ],
            "status" => "nullable|string",
            "type" => "nullable|string",
        ]);
        $validatorValidated = $validator->validated();

        $perPage = 10;
        $currentPage = $request->get("page", 1);
        $offset = ($currentPage * $perPage) - $perPage;

        $orderQuery = Order::with(['fromWallet.user', "toWallet.user",])
            ->offset($offset)->limit($perPage)
            ->where(fn ($q) => $q->where("user_id", Auth::id() /**/) /**/);

        $orders = OrderRepository::filters([
            "keyword" => $validatorValidated['keyword'] ?? null,
            "start_at" => $validatorValidated['start_at'] ?? null,
            "end_at" => $validatorValidated['end_at'] ?? null,
            "status" => $validatorValidated['status'] ?? null,
            "type" => $validatorValidated['type'] ?? null,
        ], $orderQuery)
            ->orderBy("started_at", "desc")
            ->get();

        $ordersGrouped  = $orders->groupBy(
            fn (\App\Models\Order $order) => \Carbon\Carbon::parse($order->created_at)->format('Y-m'),
        );

        $ordersPaginator = (new \Illuminate\Pagination\Paginator(
            $ordersGrouped->toArray(),
            // $users->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        ));
        $additionalsPagination  = collect(["total_retrived" => $orders->count()]);

        $mergedPaginationWithAdditionals =  $additionalsPagination->merge($ordersPaginator);

        return  response()->json(['orders' => $mergedPaginationWithAdditionals]);
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
