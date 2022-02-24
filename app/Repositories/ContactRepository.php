<?php

namespace App\Repositories;

use App\Models\Contact;

class ContactRepository
{
    public static function getUsersFromAddedContacts(int $owner_id, array $saved_ids = [])
    {
        $contacts = Contact::with("user")->where(function ($query)  use ($saved_ids, $owner_id) {
            return $query->whereIn("saved_id", $saved_ids)->where("owner_id", $owner_id);
        })->orderBy('added_at', "desc")->get()->toArray();
        return $contacts;
    }
}
