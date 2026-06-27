<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Services\IService;
use App\ValueObjects\CourseFilter;
use Illuminate\Pagination\LengthAwarePaginator;

interface ICourseService extends IService
{
    public function getBySlug(string $slug): ?Course;

    public function filter(CourseFilter $filters): LengthAwarePaginator;

    public function getFilterProps(): array;

    public function getFree(?int $perPage = null): LengthAwarePaginator;
}
