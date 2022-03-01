<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\ContactRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ContactController extends Controller
{
    public function topContacts(Request $request)
    {
        $contacts = ContactRepository::getTopContacts(Auth::id());
        $contacts = ContactResource::collection($contacts);
        return response()->json(["contacts" => $contacts]);
    }

    public function lastTransactionWithContact($savedContactID)
    {
        try {
            $contact = Contact::with("user.wallet")->where("owner_id",  Auth::id())->where("saved_id", $savedContactID)->firstOrFail();
            if (Gate::denies("view-contact", $contact)) return response("Forbidden", 403);

            $walletFromUserFromContact = $contact->user->wallet;
            $lastTransaction = TransactionRepository::lastTransactionWith(Auth::user()->wallet, $walletFromUserFromContact)
                ->first();

            return response()->json(["last_transaction" => $lastTransaction]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error_message' => "Contact not found."], 404);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function show($id)
    {
        //
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
