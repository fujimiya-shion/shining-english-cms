<?php

namespace App\Services\Admin;

use App\Repositories\Admin\IAdminRepository;
use App\Services\Service;

class AdminService extends Service implements IAdminService
{
    public function __construct(IAdminRepository $repository)
    {
        parent::__construct($repository);
    }
}
