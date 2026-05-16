<?php
namespace App\Repositories\Developer;

use App\Models\Developer;
use App\Repositories\Repository;
class DeveloperRepository extends Repository implements IDeveloperRepository {
    public function __construct(Developer $model) {
        parent::__construct($model);
    }
}