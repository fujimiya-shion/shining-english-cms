<?php

namespace App\Repositories\Contact;

use App\Models\Contact;
use App\Repositories\Repository;

class ContactRepository extends Repository implements IContactRepository
{
    protected function getDefaultOrderBy(): string
    {
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'asc';
    }

    public function __construct(Contact $model)
    {
        $this->model = $model;
    }
}
