<?php

namespace App\Http\Controllers;

use App\Exceptions\Contact\ContactBlockedException;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\ContactRepository;
use App\Responses\ErrorsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // "filter" => ["nullable",],
            "order_by" => ["nullable", Rule::in([
                'total_transaction:asc', 'total_transaction:desc',
                "last_transaction:asc",  "last_transaction:desc",
                "added_at:asc",  "added_at:desc",
                "updated_at:asc",  "updated_at:desc",
            ])],
            "favorited" => "nullable",
            "blocked" => "nullable",
            "added" => "nullable",

            // parameters for handling pagination/paginator
            "per_page" => "nullable|integer",
            "page" => "nullable|integer",
        ]);
        $validatorValidated = $validator->validated();

        $perPage = $validatorValidated['per_page'] ?? 10;
        $currentPage = $validatorValidated["page"] ?? 1;
        $offset = ($currentPage * $perPage) - $perPage;

        $contactQuery = Contact::with(['user.wallet'])->offset($offset)->limit($perPage)
            ->where(fn ($q) => $q->where("owner_id", Auth::id())
                ->where("saved_id", "!=", Auth::id())/**/);

        $contacts = ContactRepository::filters([
            // "filter" => $validatorValidated['filter'] ?? null,
            "order_by" => $validatorValidated['order_by'] ?? null,
            "blocked" =>   boolval($validatorValidated['blocked'] ?? false),
            "favorited" => boolval($validatorValidated['favorited'] ??  false),
            "added" =>  boolval($validatorValidated['added'] ?? false),
        ], $contactQuery)->get();

        $contacts = (new \Illuminate\Pagination\Paginator( // convert to pagination
            $contacts->toArray(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        ));

        return response()->json(compact("contacts"));
    }

    public function lastTransactionDetails(Contact $contact)
    {
        if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

        $contact = $contact->load("user.wallet");
        $lastTransaction  = Payment::query()->where("user_id", Auth::id())
            // ->where("status", Payment::STATUS_COMPLETED)
            ->where(
                fn ($query) => $query->where("from_wallet", $contact->user->wallet->id)
                    ->orWhere("to_wallet", $contact->user->wallet->id)
            )->orderBy("updated_at", 'desc')->first();

        return response()->json(["last_transaction" => $lastTransaction]);
    }

    public function toggleFavorite(Contact $contact)
    {
        try {
            if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

            $message = '';
            switch ($contact->status) {
                case Contact::STATUS_FAVORITED:
                    $contact->status = Contact::STATUS_ADDED;
                    $message = 'Contact removed from favorite';
                    break;
                case Contact::STATUS_BLOCKED:
                    throw new ContactBlockedException();
                    break;
                default:
                    $contact->status = Contact::STATUS_FAVORITED;
                    $message = 'Contact added to favorite';
                    break;
            }
            $contact->updated_at = now()->toDateTimeString();
            $contact->save();

            return response()->json(["message" => $message]);
        } catch (ContactBlockedException $e) {
            return $e;
        }
    }

    public function addOrRemove(User $user)
    {
        $contactQuery = Contact::query()->where("owner_id", Auth::id())->where("saved_id", $user->id);
        $isAlreadyAdded = $contactQuery->exists();

        $message = '';
        if ($isAlreadyAdded) {
            $contactQuery->delete();
            $message =  "\"$user->name\" deleted from contacts.";
        } else {
            Contact::query()->create([
                "owner_id" => Auth::id(),
                "saved_id" => $user->id,
                "status" => Contact::STATUS_ADDED,
                "added_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString(),
            ]);
            $message = "\"$user->name\" added to contacts.";
        }

        return response()->json(["message" => $message]);
    }

    public function block(Contact $contact)
    {
        if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

        $contact->status = Contact::STATUS_BLOCKED;
        return $contact->save() ?
            response()->json(['message' => "Contact blocked successfully."])
            : ErrorsResponse::server();
    }

    public function unblock(Contact $contact)
    {
        if (Gate::denies("has-contact", $contact)) return response("Forbidden", 403);

        $contact->status = Contact::STATUS_ADDED;
        $contact->updated_at = now()->toDateTimeString();
        $isSaved = $contact->save();
        return $isSaved ?
            response()->json(['message' => "Contact unblocked successfully."])
            : ErrorsResponse::server();
    }
}
