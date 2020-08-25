<?php

namespace Viloveul\Query\Search;

use Doctrine\DBAL\Connection;
use Psr\SimpleCache\CacheInterface;
use Viloveul\Query\Search\Parameter;
use Viloveul\Query\Search\ParameterInterface;

class Expression implements ExpressionInterface
{
    const REGEX_CONDITION = '/{\s?(?<type>[\%\:\=])?(?<name>\w+)\s?}/';

    /**
     * @var array
     */
    private $bindings = [];

    /**
     * @var string
     */
    private $conditions = null;

    /**
     * @var mixed
     */
    private $connection = null;

    /**
     * @var mixed
     */
    private $counter = null;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $filterable = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $orders = [];

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var mixed
     */
    private $parameter;

    /**
     * @var mixed
     */
    private $query = null;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * @var array
     */
    private $sortable = [];

    /**
     * @var int
     */
    private $total = 0;

    /**
     * @var mixed
     */
    private $tprefix = 'tprefix_';

    /**
     * @param string         $sql
     * @param Connection     $connection
     * @param CacheInterface $cache
     */
    public function __construct(string $sql, Connection $connection, CacheInterface $cache = null)
    {
        $this->connection = $connection;
        $this->cache = $cache;
        $this->query = preg_replace('/\s+/', ' ', $this->load($sql));
    }

    /**
     * @return mixed
     */
    public function compile(): void
    {

        if ($this->counter !== null) {
            $this->total = $this->connection->fetchColumn(
                $this->build($this->counter), $this->bindings, 0
            );

            if ($this->size === 0) {
                $this->size = 15;
            }

        }

        if ($this->total > 0 || $this->counter === null) {
            $query = $this->build($this->query);
            $orders = [];

            foreach ($this->orders as $key => $value) {

                if (array_key_exists($key, $this->sortable)) {
                    $orders[] = sprintf('%s %s', $this->sortable[$key], $value);
                }

            }

            if ($orders) {
                $query .= ' ORDER BY ' . implode(', ', $orders);
            }

            if ($this->size !== 0) {
                $query = $this->connection->getDatabasePlatform()->modifyLimitQuery(
                    $query, $this->size, ($this->page * $this->size) - $this->size
                );
            }

            $data = $this->connection->fetchAll($query, $this->bindings);

            $this->data = array_map(function ($row) {
                $result = new \stdClass();

                foreach ($row as $key => $value) {
                    $column = strtolower($key);
                    $result->$column = $value;
                }

                return $result;
            }, $data);
        }

    }

    public function execute(): void
    {
        $parameter = $this->parameter;

        if ($parameter === null) {
            $parameter = new Parameter($_GET ?: []);
        }

        $this->page = $parameter->getPage();
        $this->size = $parameter->getSize();
        $this->orders = $parameter->getOrders();
        $this->filters = $parameter->getFilters();
        $this->parseCondition();
        $this->compile();
    }

    /**
     * @return mixed
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param  string $sql
     * @return void
     */
    public function withCount(string $sql): void
    {
        $this->counter = preg_replace('/\s+/', ' ', $this->load($sql));
    }

    /**
     * @param $filterable
     */
    public function withFilter($filterable): void
    {

        if (is_string($filterable)) {
            $filterable = json_decode($this->load($filterable), true);
        }

        $this->filterable = is_array($filterable) ? $filterable : (array) $filterable;

    }

    /**
     * @param $sortable
     */
    public function withOrder($sortable): void
    {

        if (is_string($sortable)) {
            $sortable = json_decode($this->load($sortable), true);
        }

        $this->sortable = is_array($sortable) ? $sortable : (array) $sortable;

    }

    /**
     * @param ParameterInterface $parameter
     */
    public function withParameter(ParameterInterface $parameter): void
    {
        $this->parameter = $parameter;
    }

    /**
     * @param string $prefix
     */
    public function withPrefix(string $prefix): void
    {
        $this->tprefix = $prefix;
    }

    /**
     * @param  string   $sql
     * @return string
     */
    protected function build(string $sql): string
    {
        $query = str_replace('tprefix_', $this->tprefix, $sql);

        if ($this->conditions === null) {
            return $query;
        }

        return preg_replace_callback(
            '/\/\*\s+(where|and|or|on)\scondition\s+\*\//i',
            function ($match) {
                return sprintf('%s %s', $match[1], $this->conditions);
            },
            $query
        );
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    protected function load(string $name)
    {

        if ($this->cache !== null && $this->cache->has($name)) {
            return $this->cache->get($name);
        }

        $sql = $name;

        if (stripos($sql, 'file://') === 0) {
            $fname = substr($sql, 7);

            if (is_file($fname)) {
                $sql = file_get_contents($fname);

                if ($this->cache !== null) {
                    $this->cache->set($name, $sql);
                }

            }

        }

        return $sql;
    }

    /**
     * @return void
     */
    protected function parseCondition(): void
    {
        $conditions = [];

        foreach ($this->filterable as $filterable) {
            $clauses = is_scalar($filterable) ? [$filterable] : (array) $filterable;
            $childs = [];

            foreach ($clauses as $clause) {
                $matches = [];
                $enable = true;
                preg_match_all(static::REGEX_CONDITION, $clause, $matches);

                foreach ($matches['name'] as $name) {

                    if (!array_key_exists($name, $this->filters)) {
                        $enable = false;
                    }

                }

                if ($enable === true) {

                    foreach ($matches['name'] as $x => $name) {
                        $pos = strpos($clause, $matches[0][$x]);
                        $len = strlen($matches[0][$x]);

                        switch ($matches['type'][$x]) {
                            case ':':
                                $ar = is_scalar($this->filters[$name]) ? explode('|', $this->filters[$name]) : (array) $this->filters[$name];

                                foreach ($ar as $v) {
                                    $this->bindings[] = $v;
                                }

                                $clause = substr_replace($clause,
                                    'IN (' . implode(', ', array_fill(0, count($ar), '?')) . ')',
                                    $pos,
                                    $len
                                );
                                break;
                            case '%':
                                $this->bindings[] = '%' . preg_replace('/\s+/', '%', strtolower($this->filters[$name])) . '%';
                                $clause = substr_replace($clause, 'LIKE ?', $pos, $len);
                                break;
                            case '=':
                                $this->bindings[] = $this->filters[$name];
                                $clause = substr_replace($clause, '= ?', $pos, $len);
                                break;
                            default:
                                $this->bindings[] = $this->filters[$name];
                                $clause = substr_replace($clause, '?', $pos, $len);
                                break;
                        }

                        $childs[] = $clause;
                    }

                }

            }

            if ($childs) {
                $conditions[] = '(' . implode(' OR ', $childs) . ')';
            }

        }

        if ($conditions) {
            $this->conditions = '(' . implode(' AND ', $conditions) . ')';
        }

    }

}
