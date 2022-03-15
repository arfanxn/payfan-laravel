<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            "tx_hash" => $this->tx_hash,
            "from_wallet" => $this->whenLoaded("fromWallet", new WalletResource($this->fromWallet)),
            "to_wallet" => $this->whenLoaded("toWallet", new WalletResource($this->toWallet)),
            "status" => $this->status,
            "type" => $this->type,
            "note" => $this->note,
            "amount" => $this->amount,
            "charge" => $this->charge,
            "started_at" => $this->started_at,
            "completed_at" => $this->completed_at,
        ];
    }
}
