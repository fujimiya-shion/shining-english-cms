<?php

namespace App\ValueObjects;

class CourseFilter
{
    public function __construct(
        public ?int $categoryId = null,
        public ?int $levelId = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?float $durationMinHours = null,
        public ?float $durationMaxHours = null,
        public ?float $ratingMin = null,
        public ?float $ratingMax = null,
        public ?int $learnedMin = null,
        public ?int $learnedMax = null,
        public ?string $keyword = null,
        public ?QueryOption $options = null,
    ) {}

    public static function fromArray(array $raw): self
    {
        $options = QueryOption::fromArray($raw, true);

        $keyword = isset($raw['q']) ? trim((string) $raw['q']) : null;
        if ($keyword === '' || $keyword === null) {
            $keyword = isset($raw['name']) ? trim((string) $raw['name']) : null;
        }
        if ($keyword === '') {
            $keyword = null;
        }

        return new self(
            categoryId: isset($raw['category_id']) ? (int) $raw['category_id'] : null,
            levelId: isset($raw['level_id']) ? (int) $raw['level_id'] : null,
            priceMin: isset($raw['price_min']) ? (int) $raw['price_min'] : null,
            priceMax: isset($raw['price_max']) ? (int) $raw['price_max'] : null,
            durationMinHours: isset($raw['duration_min_hours']) ? (float) $raw['duration_min_hours'] : null,
            durationMaxHours: isset($raw['duration_max_hours']) ? (float) $raw['duration_max_hours'] : null,
            ratingMin: isset($raw['rating_min']) ? (float) $raw['rating_min'] : null,
            ratingMax: isset($raw['rating_max']) ? (float) $raw['rating_max'] : null,
            learnedMin: isset($raw['learned_min']) ? (int) $raw['learned_min'] : null,
            learnedMax: isset($raw['learned_max']) ? (int) $raw['learned_max'] : null,
            keyword: $keyword,
            options: $options,
        );
    }
}
