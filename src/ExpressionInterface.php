<?php

namespace Viloveul\Query\Search;

interface ExpressionInterface
{
    /**
     * @return void
     */
    public function compile(): void;

    /**
     * @return void
     */
    public function execute(): void;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @param  string $sql
     * @return void
     */
    public function withCount(string $sql): void;

    /**
     * @param  $filterable
     * @return void
     */
    public function withFilter($filterable): void;

    /**
     * @param  $sortable
     * @return void
     */
    public function withOrder($sortable): void;

    /**
     * @param  ParameterInterface $parameter
     * @return void
     */
    public function withParameter(ParameterInterface $parameter): void;

    /**
     * @param  string $prefix
     * @return void
     */
    public function withPrefix(string $prefix): void;
}
