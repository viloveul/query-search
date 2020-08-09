<?php

namespace Viloveul\Query\Search;

use Viloveul\Query\Search\ParameterInterface;

class Parameter implements ParameterInterface
{
    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $size = 0;

    /**
     * @param array  $params
     * @param string $prefix
     */
    public function __construct(array $params, string $prefix = '')
    {

        if (array_key_exists($prefix . 'filter', $params) && is_array($params[$prefix . 'filter'])) {

            foreach ($params[$prefix . 'filter'] as $key => $value) {
                $this->addFilter($key, $value);
            }

        } else {

            foreach ($params as $key => $value) {

                if ('filter_' === substr($key, strlen($prefix), 7)) {
                    $this->addFilter(substr($key, 7 + strlen($prefix)), $value);
                }

            }

        }

        if (array_key_exists($prefix . 'page', $params)) {
            $page = array_key_exists($prefix . 'page', $params) ? $params[$prefix . 'page'] : [];

            $this->setPage(abs(is_scalar($page) ? $page : 0) ?: 1);

            if (is_array($page)) {

                if (array_key_exists('number', $page)) {
                    $this->setPage(abs($page['number']) ?: 1);
                }

                if (array_key_exists('limit', $page)) {
                    $this->setSize(abs($page['limit']) ?: 15);
                }

            }

        } else {
            $this->setPage(
                abs(array_key_exists($prefix . 'page_number', $params) ? $params[$prefix . 'page_number'] : 0) ?: 1
            );

            if (array_key_exists($prefix . 'page_limit', $params)) {
                $this->setSize(abs($params[$prefix . 'page_limit']) ?: 15);
            }

        }

        if (array_key_exists($prefix . 'sort', $params) && !empty($params[$prefix . 'sort'])) {
            $tempArr = explode(',', $params[$prefix . 'sort']);

            foreach ($tempArr as $tmp) {

                if (strpos($tmp, ':') !== false) {
                    $part = explode(':', $tmp);
                    $this->orders[$part[0]] = strtolower($part[1]) === 'desc' ? 'DESC' : 'ASC';
                } else {
                    $x = trim($tmp);

                    if ('-' === substr($x, 0, 1)) {
                        $this->orders[substr($x, 1)] = 'DESC';
                    } else {
                        $this->orders[$x] = 'ASC';
                    }

                }

            }

        } else {

            foreach (['asc', 'desc'] as $x) {

                if (array_key_exists($prefix . 'sort_' . $x, $params) && !empty($params[$prefix . 'sort_' . $x])) {
                    $orderColumns = explode(',', $params[$prefix . 'sort_' . $x]);

                    foreach ($orderColumns as $key) {
                        $this->addOrder(trim($key), $x);
                    }

                }

            }

        }

    }

    /**
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public function addFilter(string $name, $value): void
    {

        if (is_scalar($value) && strlen($value) > 0) {
            $this->filters[$name] = $value;
        }

    }

    /**
     * @param  string $name
     * @param  string $mode
     * @return void
     */
    public function addOrder(string $name, string $mode = 'asc'): void
    {
        $this->orders[$name] = $mode === 'asc' ? 'ASC' : 'DESC';
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param  int    $page
     * @return void
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * @param  int    $size
     * @return void
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

}
