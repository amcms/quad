<?php

use PHPUnit\Framework\TestCase;

class FiltersTest extends TestCase {

    public function setUp() {
        $this->filters = new \Amcms\Quad\Filters;
    }

    public function testFilterEquals() {
        $this->assertEquals(true, $this->filters->filterEquals(1, 1));
        $this->assertEquals(true, $this->filters->filterEquals(1, '1'));
        $this->assertEquals(false, $this->filters->filterEquals(1, 2));
    }

    public function testFilterNot() {
        $this->assertEquals(true, $this->filters->filterNot(1, 2));
        $this->assertEquals(false, $this->filters->filterNot(1, '1'));
        $this->assertEquals(false, $this->filters->filterNot(1, 1));
    }

    public function testFilterGreaterThan() {
        $this->assertEquals(true, $this->filters->filterGreaterThan(2, 1));
        $this->assertEquals(false, $this->filters->filterGreaterThan(2, 2));
        $this->assertEquals(false, $this->filters->filterGreaterThan(2, 3));
    }

    public function testFilterGreaterThanOrEquals() {
        $this->assertEquals(true, $this->filters->filterGreaterThanOrEquals(2, 1));
        $this->assertEquals(true, $this->filters->filterGreaterThanOrEquals(2, 2));
        $this->assertEquals(false, $this->filters->filterGreaterThanOrEquals(2, 3));
    }

    public function testFilterLowerThan() {
        $this->assertEquals(false, $this->filters->filterLowerThan(2, 1));
        $this->assertEquals(false, $this->filters->filterLowerThan(2, 2));
        $this->assertEquals(true, $this->filters->filterLowerThan(2, 3));
    }

    public function testFilterLowerThanOrEquals() {
        $this->assertEquals(false, $this->filters->filterLowerThanOrEquals(2, 1));
        $this->assertEquals(true, $this->filters->filterLowerThanOrEquals(2, 2));
        $this->assertEquals(true, $this->filters->filterLowerThanOrEquals(2, 3));
    }

    public function testFilterContains() {
        $this->assertEquals(true, $this->filters->filterContains('abc', 'a'));
        $this->assertEquals(true, $this->filters->filterContains('abc', 'A'));
        $this->assertEquals(false, $this->filters->filterContains('abc', 'd'));
    }

    public function testFilterContainsNot() {
        $this->assertEquals(false, $this->filters->filterContainsNot('abc', 'a'));
        $this->assertEquals(false, $this->filters->filterContainsNot('abc', 'A'));
        $this->assertEquals(true, $this->filters->filterContainsNot('abc', 'd'));
    }

    public function testFilterIn() {
        $this->assertEquals(false, $this->filters->filterIn('a', 'abc'));
        $this->assertEquals(true, $this->filters->filterIn('a', 'a,b,c'));
        $this->assertEquals(false, $this->filters->filterIn('a', 'A,b,c'));
    }

    public function testFilterNotIn() {
        $this->assertEquals(true, $this->filters->filterNotIn('a', 'abc'));
        $this->assertEquals(false, $this->filters->filterNotIn('a', 'a,b,c'));
        $this->assertEquals(true, $this->filters->filterNotIn('a', 'A,b,c'));
    }

    public function testFilterEmpty() {
        $this->assertEquals(false, $this->filters->filterEmpty(1));
        $this->assertEquals(true, $this->filters->filterEmpty(0));
        $this->assertEquals(true, $this->filters->filterEmpty(null));
    }

    public function testFilterNotEmpty() {
        $this->assertEquals(true, $this->filters->filterNotEmpty(1));
        $this->assertEquals(false, $this->filters->filterNotEmpty(0));
        $this->assertEquals(false, $this->filters->filterNotEmpty(null));
    }

    public function testModifierAppend() {
        $this->assertEquals('12', $this->filters->modifierAppend(1, 2));
        $this->assertEquals('12', $this->filters->modifierAppend(1, '2'));
        $this->assertEquals('12', $this->filters->modifierAppend('1', 2));
        $this->assertEquals('12', $this->filters->modifierAppend('1', '2'));
        $this->assertEquals('1', $this->filters->modifierAppend(1, null));
        $this->assertEquals('1', $this->filters->modifierAppend(1, [2]));
        $this->assertEquals([1], $this->filters->modifierAppend([1], [2]));
    }

}