<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            "from_wallet" => $this->whenLoaded("fromWallet", new WalletResource($this->fromWallet)),
            "to_wallet" => $this->whenLoaded("toWallet", new WalletResource($this->toWallet)),
            "transaction_id" => $this->transaction_id,
            "note" => $this->note,
            "type" => $this->type,
            "status" => $this->status,
            "amount" => $this->amount,
            "charge" => $this->charge,
            "started_at" => $this->started_at,
            "completed_at" => $this->completed_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
