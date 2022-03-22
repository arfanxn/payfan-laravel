<?php

namespace App\Http\Resources;

use App\Traits\HasPaginationResourceCollectionTrait;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    use HasPaginationResourceCollectionTrait;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_merge($this->pagination, [
            'data' => OrderResource::collection($this->collection),
        ]);
    }
}
