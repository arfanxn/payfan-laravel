<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            "owner_id" => $this->owner_id,
            "saved_id" => $this->saved_id,
            "status" => $this->status,
            "total_transaction" => $this->total_transaction,
            "last_transaction" => $this->last_transaction,
            'added_at' => $this->added_at,
            "user" => $this->whenLoaded("user", new UserResource($this->user)),
        ];
    }
}
