<?php

namespace App\Http\Resources;

use App\Traits\HasPaginationResourceCollectionTrait;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\Paginator;

class TransactionCollection extends ResourceCollection
{
    use HasPaginationResourceCollectionTrait;
    // use HasPaginationResourceCollectionTrait {
    //     HasPaginationResourceCollectionTrait::__construct as private  __prcConstruct;
    // }

    // public function __construct($resource)
    // {
    //     $this->__prcConstruct($resource);
    // }

    // public function __construct($resource, array|null $additionals = null)
    // {
    //     if ($resource instanceof Paginator) {
    //         $paginator = $resource;
    //         $paginatorArrayed  = $paginator->toArray();
    //         $this->pagination =  $paginatorArrayed;

    //         if (is_array($additionals))
    //             $this->pagination = array_merge($this->pagination, $additionals);

    //         parent::__construct($paginator->getCollection());
    //     }

    //     parent::__construct($resource);
    // }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_merge($this->pagination, [
            'data' => TransactionResource::collection($this->collection),
        ]);
    }
}
