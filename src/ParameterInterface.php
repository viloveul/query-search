<?php

namespace Viloveul\Query\Search;

interface ParameterInterface
{
    /**
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public function addFilter(string $name, $value): void;

    /**
     * @param  string $name
     * @param  string $mode
     * @return void
     */
    public function addOrder(string $name, string $mode = 'asc'): void;

    /**
     * @return array
     */
    public function getFilters(): array;

    /**
     * @return array
     */
    public function getOrders(): array;

    /**
     * @return int
     */
    public function getPage(): int;

    /**
     * @return int
     */
    public function getSize(): int;
}
