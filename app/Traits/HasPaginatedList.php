<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasPaginatedList
{
    abstract protected static function filteredQuery(array $filters): Builder;

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        $paginator = static::filteredQuery($filters)->paginate(static::ADMIN_PAGE_SIZE);

        $paginator->withQueryString();

        $paginator->through(fn($model) => $model->toListRow());

        return $paginator;
    }
}
