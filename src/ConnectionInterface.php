<?php

namespace Viloveul\Query\Search;

interface ConnectionInterface
{
    /**
     * @param string $query
     * @param int    $size
     * @param int    $index
     */
    public function compileLimit(string $query, int $size, int $index);

    /**
     * @param string $query
     * @param array  $bindings
     */
    public function fetchAll(string $query, array $bindings);

    /**
     * @param string $query
     * @param array  $bindings
     * @param int    $index
     */
    public function fetchColumn(string $query, array $bindings, int $index);
}
