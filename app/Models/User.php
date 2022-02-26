<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        "profile_pict",
        "email_verified_at"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function markEmailAsVerified()
    {
        $isMarkingSuccess = $this->update([
            "email_verified_at" => now()->toDateTimeString(),
        ]);
        return $isMarkingSuccess ?  $this : throw new Exception("user->email_verified_at : update fail!");
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class, "user_id");
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, "user_id");
    }


    public function contacts()
    {
        return $this->hasMany(Contact::class, "owner_id");
    }

    public function isAddedBySelf($owner_id = null)
    {
        $owner_id = Auth::id();
        return $this->hasOne(Contact::class, "saved_id")->where("owner_id", $owner_id);
    }
}
