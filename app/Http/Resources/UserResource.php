<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\URLHelper;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "fullname" => $this->name,
            "email" => $this->email,
            "email_verified_at" => $this->email_verified_at,
            "profile_pict" => URLHelper::userProfilePict(isset($this->profile_pict) && $this->profile_pict  ? $this->profile_pict : null),
            "is_added_by_self" => $this->whenLoaded("isAddedBySelf", new UserResource($this->is_added_by_self)),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "settings" => $this->whenLoaded("settings", fn () => $this->settings)
        ];
    }
}
