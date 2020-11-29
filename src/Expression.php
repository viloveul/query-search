<?php

namespace Viloveul\Query\Search;

use Closure;
use Psr\SimpleCache\CacheInterface;
use Viloveul\Query\Search\Parameter;
use Viloveul\Query\Search\ParameterInterface;
use Viloveul\Query\Search\ConnectionInterface;

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
     * @var mixed
     */
    private $listeners = [];

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
    private $prefix = 'prefix_';

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
     * @param string              $sql
     * @param ConnectionInterface $connection
     * @param CacheInterface      $cache
     */
    public function __construct(string $sql, ConnectionInterface $connection, CacheInterface $cache = null)
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
            $cstart = microtime(true);
            $cquery = $this->build($this->counter);
            $this->total = $this->connection->fetchColumn($cquery, $this->bindings, 0);
            $this->fireEventListener($cquery, $cstart);

            if ($this->size === 0) {
                $this->size = 15;
            }

        }

        if ($this->total > 0 || $this->counter === null) {
            $start = microtime(true);
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
                $query = $this->connection->compileLimit(
                    $query, $this->size, ($this->page * $this->size) - $this->size
                );
            }

            $data = $this->connection->fetchAll($query, $this->bindings);
            $this->fireEventListener($cquery, $cstart);

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

    /**
     * @param $config
     */
    public function configure($config): void
    {

        if (is_string($config)) {
            $config = json_decode($this->load($config), true) ?: [];
        }

        if (array_key_exists('where', $config)) {
            $this->filterable = is_array($config['where']) ? $config['where'] : (array) $config['where'];
        }

        if (array_key_exists('order', $config)) {
            $this->sortable = is_array($config['order']) ? $config['order'] : (array) $config['order'];
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
     * @param Closure $handler
     */
    public function listen(Closure $handler): void
    {
        $this->listeners[] = $handler;
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
        $this->prefix = $prefix;
    }

    /**
     * @param  string   $sql
     * @return string
     */
    protected function build(string $sql): string
    {
        $query = str_replace('prefix_', $this->prefix, $sql);

        if ($this->conditions === null) {
            return preg_replace('/\/\*\s+(where|and|or|on)\scondition\s+\*\//i', '', $query);
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
     * @param $start
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
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

    /**
     * @param string   $query
     * @param $start
     */
    private function fireEventListener(string $query, $start)
    {
        $time = $this->getElapsedTime($start);

        foreach ($this->listeners as $handler) {
            $handler($query, $this->bindings, $time);
        }

    }

}
