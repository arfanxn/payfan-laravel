<?php

namespace App\Traits;

use Illuminate\Pagination\Paginator;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContracts;

trait HasPaginationResourceCollectionTrait
{
    public function __construct($resource, array|null $additionals = null)
    {
        if ($resource instanceof Paginator) {
            $paginator = $resource;
            $paginatorArrayed  = $paginator->toArray();
            unset($paginatorArrayed['data']); // remove data from array 
            $this->pagination =  $paginatorArrayed;

            if (is_array($additionals)) {
                if (array_key_exists("data", $additionals)) {
                    throw new \InvalidArgumentException("additionals argument must be an associative array and not with key 'data' , because 'data' has already been set in the collection");
                }
                $this->pagination = array_merge($this->pagination, $additionals);
            }

            parent::__construct($paginator->getCollection());
        }

        parent::__construct($resource);
    }
}
