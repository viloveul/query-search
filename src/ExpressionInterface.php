<?php

namespace Viloveul\Query\Search;

use Closure;

interface ExpressionInterface
{
    /**
     * @return void
     */
    public function compile(): void;

    /**
     * @param  $configure
     * @return void
     */
    public function configure($configure): void;

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
     * @param Closure $listener
     */
    public function listen(Closure $listener): void;

    /**
     * @param  string $sql
     * @return void
     */
    public function withCount(string $sql): void;

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
