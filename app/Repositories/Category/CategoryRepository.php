<?php

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\Repository;

class CategoryRepository extends Repository implements ICategoryRepository
{
    protected function getDefaultOrderBy(): string
    {
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'desc';
    }

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function getCourseFilterCategories(): array
    {
        return $this->model->newQuery()
            ->whereHas('courses', fn ($query) => $query->where('status', true))
            ->withCount([
                'courses as courses_count' => fn ($query) => $query->where('status', true),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'course_count' => $category->courses_count ?? 0,
            ])
            ->values()
            ->all();
    }
}
