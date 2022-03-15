<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            "user_id" => $this->user_id,
            "user" => $this->whenLoaded("user", $this->user),
            "address" => $this->address,
            "balance" => $this->balance,
            "total_transaction" => $this->total_transaction,
            "last_transaction" => $this->last_transaction,
        ];
    }
}
