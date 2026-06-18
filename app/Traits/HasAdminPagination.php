<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasAdminPagination
{
  protected static function paginateForAdmin(Builder $query): LengthAwarePaginator
  {
    return $query
      ->paginate(static::ADMIN_PAGE_SIZE)
      ->withQueryString();
  }
}
