<?php

namespace App\Repositories\Course;

use App\Models\Course;
use App\Repositories\IRepository;
use App\ValueObjects\CourseFilter;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

interface ICourseRepository extends IRepository
{
    public function getBySlug(string $slug): ?Course;

    public function filter(CourseFilter $filters): LengthAwarePaginator;

    public function getFilterProps(): array;

    public function getFree(?QueryOption $options = null): LengthAwarePaginator;
}
