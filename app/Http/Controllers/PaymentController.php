<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentCollection;
use App\Http\Resources\TransactionCollection;
use App\Models\Transaction;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
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

            // pagination config
            "per_page" => "nullable|numeric",
            "page" => "nullable|numeric",
        ]);
        $validatorValidated = $validator->validated();

        $perPage = $validatorValidated["per_page"] ?? 10;
        $currentPage = $validatorValidated["page"] ?? 1;
        $offset = ($currentPage * $perPage) - $perPage;

        $paymentQuery = Payment::with(['fromWallet.user', "toWallet.user",])
            ->offset($offset)->limit($perPage)
            ->where(fn ($q) => $q->where("user_id", Auth::id() /**/) /**/);

        $payments = PaymentRepository::filters([
            "keyword" => $validatorValidated['keyword'] ?? null,
            "start_at" => $validatorValidated['start_at'] ?? null,
            "end_at" => $validatorValidated['end_at'] ?? null,
            "status" => $validatorValidated['status'] ?? null,
            "type" => $validatorValidated['type'] ?? null,
        ], $paymentQuery)
            ->orderBy("created_at", "desc")
            ->get();

        $paymentsGrouped  = $payments->groupBy(
            fn (\App\Models\Payment $payment) =>
            \Carbon\Carbon::parse($payment->created_at)->format('Y-m'),
        );

        $paymentsPaginator = (new \Illuminate\Pagination\Paginator(
            $paymentsGrouped->toArray(),
            // $users->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        ));
        $additionalsPagination  = collect(["total_retrived" => $payments->count()]);

        $mergedPaginationWithAdditionals =  $additionalsPagination->merge($paymentsPaginator);

        return  response()->json(['payments' => $mergedPaginationWithAdditionals]);
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
    public function show(Payment $payment)
    {
        if (Gate::denies("has-payment", $payment)) return response("Forbidden", 403);
        return response()->json(["payment" => $payment]);
    }
}
