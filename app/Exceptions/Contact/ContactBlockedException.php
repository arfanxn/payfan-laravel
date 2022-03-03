<?php

namespace App\Exceptions\Contact;

use Exception;

class ContactBlockedException extends Exception
{
    public function render()
    {
        return response()->json(['error_message' => "Contact is Blocked."]);
    }
}
