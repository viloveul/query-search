<?php

use Viloveul\Query\Search\Parameter;
use PHPUnit\Framework\TestCase;

final class ParameterTest extends TestCase
{
    public function testCanGetFilterValue(): void
    {
        $parameter1 = new Parameter(['filter' => ['name' => 'fajrul']]);
        $this->assertArrayHasKey('name', $parameter1->getFilters());
        $parameter2 = new Parameter(['filter_name' => 'fajrul']);
        $this->assertArrayHasKey('name', $parameter2->getFilters());
    }

    public function testCanGetOrderValue(): void
    {
        $parameter1 = new Parameter(['sort' => 'name']);
        $this->assertArrayHasKey('name', $parameter1->getOrders());
        $parameter2 = new Parameter(['sort_asc' => 'name']);
        $this->assertArrayHasKey('name', $parameter2->getOrders());
    }

    public function testCanGetPageValue(): void
    {
        $parameter1 = new Parameter(['page' => 3]);
        $this->assertSame(3, $parameter1->getPage());
        $parameter2 = new Parameter(['page' => ['limit' => 10, 'number' => 3]]);
        $this->assertSame(3, $parameter2->getPage());
        $this->assertSame(10, $parameter2->getSize());
        $parameter3 = new Parameter(['page_number' => 3]);
        $this->assertSame(3, $parameter3->getPage());
    }
}
