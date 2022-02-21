<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class URLHelper
{
    public static function userProfilePict($profile_pict_filename = null)
    {
        if (is_null($profile_pict_filename) || !$profile_pict_filename)
            $profile_pict_filename = "default-profile-pict.png";
        // else if (Storage::exists(public_path())) {
        // }

        return asset(Storage::url("images/user/profile_pict/$profile_pict_filename"));
    }
}
