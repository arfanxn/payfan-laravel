<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactResource;
use App\Http\Resources\UserResource;
use App\Models\Contact;
use App\Models\User;
use App\Repositories\ContactRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SearchPeopleController extends Controller
{
    public function searchExceptSelf(Request $request) // search people except self (self == the logged in user)
    {
        $validator  = Validator::make($request->only(["keyword"]), [
            "keyword" => "required|string|max:50"
        ]);
        if ($validator->fails())  return response()->json($validator->errors()->messages(), 422);
        $keyword = $validator->validated()["keyword"];

        $users = User::with(["isAddedBySelf"])->where("id", "!=", Auth::id())->where(function ($query) use ($keyword) {
            return $query->where("name", "LIKE", "%$keyword%")->orWhere("email", "LIKE", "%$keyword%");
        })->orderBy("created_at", "desc")->get();

        $user_ids = array_map(fn ($user) => $user["id"], $users->toArray());
        $usersInContacts = ContactRepository::getUsersFromAddedContacts(Auth::id(), $user_ids)
            ->orderBy('added_at', "desc")->get();

        if (!empty($users->toArray())) {
            $users = UserResource::collection($users);
        }
        if (!empty($usersInContacts->toArray())) {
            $usersInContacts = ContactResource::collection($usersInContacts);
        }


        return response()->json([
            "message" => ($users || $usersInContacts) ? "Results for $keyword"
                : "No results for $keyword",
            "users" => $users ?? null, "contacts" => $usersInContacts ?? null
        ]);
    }
}
