<?php

namespace App\Infrastructure\Shared\Http\Pagination;

readonly class Paginator
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
    ) {
    }

    /**
     * @return array{page: int, limit: int, total: int, total_pages: int}
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'total_pages' => 0 === $this->limit ? 0 : (int) ceil($this->total / $this->limit),
        ];
    }
}
