<?php

namespace App\Http\Controllers;

use App\Exceptions\Contact\ContactBlockedException;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\User;
use App\Repositories\ContactRepository;
use App\Repositories\TransactionRepository;
use App\Responses\ErrorsResponse;
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

    public function lastTransactionDetail(Contact $contact)
    {
        if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

        $walletFromUserFromContact = ($contact->load("user.wallet"))->user->wallet;
        $lastTransaction = TransactionRepository::lastTransactionWith(Auth::user()->wallet, $walletFromUserFromContact)
            ->first();

        return response()->json(["last_transaction" => $lastTransaction]);
    }

    public function toggleFavorite(Contact $contact)
    {
        try {
            if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

            $message = '';
            $statusText = '';
            switch ($contact->status) {
                case Contact::STATUS_FAVORITED:
                    $contact->status = Contact::STATUS_ADDED;
                    $message = "UNFAVORITED";
                    $statusText = 'Contact removed from favorite';
                    break;
                case Contact::STATUS_BLOCKED:
                    throw new ContactBlockedException();
                    break;
                default:
                    $contact->status = Contact::STATUS_FAVORITED;
                    $message = Contact::STATUS_FAVORITED;
                    $statusText = 'Contact added to favorite';
                    break;
            }
            $contact->save();

            return response()->json(["message" => $message])->setStatusCode(200, $statusText);
        } catch (ContactBlockedException $e) {
            return $e;
        }
    }

    public function addOrRemove(User $user)
    {
        $contactQuery = Contact::query()->where("owner_id", Auth::id())->where("saved_id", $user->id);
        $isAlreadyAdded = $contactQuery->exists();

        $statusText = "";
        $message = '';
        if ($isAlreadyAdded) {
            $contactQuery->delete();
            $message = "Removed";
            $statusText = "User deleted from contact.";
        } else {
            Contact::query()->create([
                "owner_id" => Auth::id(),
                "saved_id" => $user->id,
                "status" => Contact::STATUS_ADDED,
                "added_at" => now()->toDateTimeString(),
            ]);
            $message = "Added";
            $statusText = "User added to contact.";
        }

        return response()->json(["message" => $message])->setStatusCode(200, $statusText);
    }

    public function block(Contact $contact)
    {
        if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

        $contact->status = Contact::STATUS_BLOCKED;
        return $contact->save() ?
            response()->json(['message' => "Contact blocked successfully."])->setStatusCode(200, "Contact blocked successfully.")
            : ErrorsResponse::server();
    }
}
