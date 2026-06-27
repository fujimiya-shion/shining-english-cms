<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Repositories\Course\ICourseRepository;
use App\Services\Service;
use App\ValueObjects\CourseFilter;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService extends Service implements ICourseService
{
    protected ICourseRepository $courseRepository;

    public function __construct(ICourseRepository $repository)
    {
        parent::__construct($repository);
        $this->courseRepository = $repository;
    }

    public function getBySlug(string $slug): ?Course
    {
        return $this->courseRepository->getBySlug($slug);
    }

    public function filter(CourseFilter $filters): LengthAwarePaginator
    {
        return $this->courseRepository->filter($filters);
    }

    public function getFilterProps(): array
    {
        return $this->courseRepository->getFilterProps();
    }

    public function getFree(?int $perPage = null): LengthAwarePaginator
    {
        $options = $perPage !== null ? new QueryOption(perPage: $perPage) : null;

        return $this->courseRepository->getFree($options);
    }
}
