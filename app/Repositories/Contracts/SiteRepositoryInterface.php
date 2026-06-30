<?php

namespace App\Repositories\Contracts;

interface SiteRepositoryInterface extends BaseRepositoryInterface
{
    public function countActive(): int;
}
