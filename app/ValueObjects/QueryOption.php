<?php

namespace App\ValueObjects;

class QueryOption
{
    public function __construct(
        public ?int $page = null,
        public int $perPage = 15,
        /** @var string[] */
        public array $with = [],
        public string $orderBy = 'created_at',
        public string $orderDirection = 'desc',
    ) {}

    /* =========================
     |  Getters
     ========================= */

    public function getPage(): ?int
    {
        if ($this->page === null) {
            throw new \TypeError('QueryOption page is not set.');
        }

        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return string[]
     */
    public function getWith(): array
    {
        return $this->with;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    /* =========================
     |  Setters (chainable)
     ========================= */

    public function setPage(?int $page = null): self
    {
        if ($page === null) {
            return $this;
        }
        $this->page = max(1, $page);

        return $this;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = max(1, $perPage);

        return $this;
    }

    /**
     * @param  string[]  $with
     */
    public function setWith(array $with): self
    {
        // lọc chỉ string
        $this->with = array_values(array_filter($with, 'is_string'));

        return $this;
    }

    public function setOrderBy(string $orderBy): self
    {
        $orderBy = trim($orderBy);
        if ($orderBy !== '') {
            $this->orderBy = $orderBy;
        }

        return $this;
    }

    public function setOrderDirection(string $orderDirection): self
    {
        $orderDirection = strtolower(trim($orderDirection));
        $this->orderDirection = $orderDirection === 'asc' ? 'asc' : 'desc';

        return $this;
    }

    /* =========================
     |  Factory
     ========================= */

    public static function fromArray(array $raw, bool $forcePagination = false): self
    {
        $dto = new self;

        if ($forcePagination) {
            $dto->setPage(1);
            $dto->setPerPage(config('const.pagination.default_per_page'));
        }

        if (isset($raw['page'])) {
            $dto->setPage((int) $raw['page']);
        }

        if (isset($raw['perPage'])) {
            $dto->setPerPage((int) $raw['perPage']);
        }

        if (isset($raw['with'])) {
            // hỗ trợ cả "a,b,c" lẫn array
            $with = is_string($raw['with'])
                ? explode(',', $raw['with'])
                : (array) $raw['with'];

            $dto->setWith($with);
        }

        if (isset($raw['orderBy'])) {
            $dto->setOrderBy((string) $raw['orderBy']);
        }

        if (isset($raw['orderDirection'])) {
            $dto->setOrderDirection((string) $raw['orderDirection']);
        }

        return $dto;
    }
}
