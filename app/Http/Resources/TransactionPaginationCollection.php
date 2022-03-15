<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\Paginator as PaginationPaginator;

class TransactionPaginationCollection extends ResourceCollection
{
    public function __construct(PaginationPaginator $resource)
    {
        $this->pagination = [
            'total' => $resource->total(),
            'count' => $resource->count(),
            'per_page' => $resource->perPage(),
            'current_page' => $resource->currentPage(),
            'total_pages' => $resource->lastPage()
        ];

        $resource = $resource->getCollection();

        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => TransactionResource::collection($this->collection),
            ...$this->pagination
        ];
    }
}
