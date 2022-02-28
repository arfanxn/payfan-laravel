<?php

namespace App\Repositories;

use App\Models\Contact;

class ContactRepository
{
    public static function whereNotBlocked(int $owner_id)
    {
        return Contact::with("user")->where("owner_id", $owner_id)
            ->where("status", "!=", Contact::STATUS_BLOCKED)->orderBy("last_transaction", 'desc');
    }

    public static function usersFromAddedContacts(int $owner_id, array $saved_ids = [])
    {
        $contacts = Contact::with("user")->where("status", "!=", Contact::STATUS_BLOCKED)
            ->where(function ($query)  use ($saved_ids, $owner_id) {
                return $query->whereIn("saved_id", $saved_ids)->where("owner_id", $owner_id);
            });
        return $contacts;
    }
}
