<?php

namespace App\Repositories\Contact;

use App\Models\Contact;
use App\Repositories\Repository;

class ContactRepository extends Repository implements IContactRepository
{
    public function __construct(Contact $model)
    {
        $this->model = $model;
    }
}
