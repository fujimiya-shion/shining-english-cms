<?php

namespace App\Repositories\Admin;

use App\Models\Admin;
use App\Repositories\Repository;

class AdminRepository extends Repository implements IAdminRepository
{
    protected function getDefaultOrderBy(): string
    {
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'asc';
    }

    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }
}
