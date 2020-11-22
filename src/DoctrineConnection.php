<?php

namespace Viloveul\Query\Search;

use Doctrine\DBAL\Connection;

class DoctrineConnection implements ConnectionInterface
{
    /**
     * @var mixed
     */
    protected $doctrine;

    /**
     * @param Connection $doctrine
     */
    public function __construct(Connection $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $query
     * @param int    $size
     * @param int    $index
     */
    public function compileLimit(string $query, int $size, int $index): string
    {
        return $this->doctrine->getDatabasePlatform()->modifyLimitQuery($query, $size, $index);
    }

    /**
     * @param string $query
     * @param array  $bindings
     */
    public function fetchAll(string $query, array $bindings)
    {
        return $this->doctrine->fetchAll($query, $bindings);
    }

    /**
     * @param string $query
     * @param array  $bindings
     * @param int    $index
     */
    public function fetchColumn(string $query, array $bindings, int $index)
    {
        return $this->doctrine->fetchColumn($query, $bindings, $index);

    }
}
