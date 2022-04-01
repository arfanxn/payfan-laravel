<?php

namespace App\Repositories;

use App\Models\Contact;

class ContactRepository
{
    public static function getTopContacts(int $owner_id)
    {
        $maxGetLimit = 50;
        $favoritedContact = Contact::with([
            "user.wallet" => fn ($q) => $q->select(['user_id', 'address'])
        ])->where("owner_id", $owner_id)
            ->where("status", Contact::STATUS_FAVORITED)->orderBy("last_transaction", 'desc')->limit($maxGetLimit)->get();

        $arrayedFavoritedContact = $favoritedContact->toArray();
        if (count($arrayedFavoritedContact) >= $maxGetLimit)
            return $favoritedContact;

        $addedContact = Contact::with(["user.wallet"])->where("owner_id", $owner_id)
            ->where("status", Contact::STATUS_ADDED)->orderBy("last_transaction", 'desc')
            ->limit($maxGetLimit -  count($arrayedFavoritedContact))->get();

        return $favoritedContact->merge($addedContact);
    }

    public static function where_OwnID_andWhereIn_SavedID(int $owner_id, array $saved_ids = [])
    {
        $contacts = Contact::query()->where("status", "!=", Contact::STATUS_BLOCKED)
            ->where(function ($query)  use ($saved_ids, $owner_id) {
                return $query->whereIn("saved_id", $saved_ids)->where("owner_id", $owner_id);
            });
        return $contacts;
    }
}
