<?php

namespace App\Services\Category;

use App\Repositories\Category\ICategoryRepository;
use App\Services\Service;

class CategoryService extends Service implements ICategoryService
{
    public function __construct(ICategoryRepository $repository)
    {
        parent::__construct($repository);
    }
}
