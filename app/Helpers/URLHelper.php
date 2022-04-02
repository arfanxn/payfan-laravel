<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class URLHelper
{
    public static function userProfilePict($profile_pict_filename = null)
    {
        if (Str::contains($profile_pict_filename, "#")) return $profile_pict_filename;

        $profile_pict_filename = Str::afterLast($profile_pict_filename, "/");

        $path = "images/user/profile_pict/";
        $default = "default-profile-pict.png";

        if (is_null($profile_pict_filename) || !$profile_pict_filename)
            $profile_pict_filename = $default;

        if (!Storage::disk("public")->exists($path . $profile_pict_filename)) {
            $profile_pict_filename = $default;
        }

        return asset(Storage::url($path . $profile_pict_filename));
    }

    public static function frontendWeb(string $url = '')
    {
        $url = preg_replace("/^\//", "",  $url);
        return "http://localhost:8081/" . $url;
    }
}
