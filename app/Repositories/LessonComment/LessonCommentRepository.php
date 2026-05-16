<?php

namespace App\Repositories\LessonComment;

use App\Models\LessonComment;
use App\Repositories\Repository;

class LessonCommentRepository extends Repository implements ILessonCommentRepository
{
    public function __construct(LessonComment $model)
    {
        parent::__construct($model);
    }
}
