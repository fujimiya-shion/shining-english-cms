<?php

namespace App\Repositories\Quiz;

use App\Models\Quiz;
use App\Repositories\Repository;

class QuizRepository extends Repository implements IQuizRepository
{
    protected function getDefaultOrderBy(): string
    {
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'desc';
    }

    public function __construct(Quiz $model)
    {
        parent::__construct($model);
    }
}
