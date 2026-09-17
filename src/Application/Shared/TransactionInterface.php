<?php

namespace App\Application\Shared;

interface TransactionInterface
{
    /**
     * Runs $callback atomically: if it throws, everything it did is rolled back.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
