<?php

namespace App\Casts;

use App\Helpers\URLHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class UserProfilePictCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return URLHelper::userProfilePict($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return URLHelper::userProfilePict($value);
    }
}
