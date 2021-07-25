<?php

namespace Hamlet\Database\Traits;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class SplitterTraitTest
{
    public function testSelectValueSplitter()
    {
        $splitter = $this->selectValueSplitter('firstName');
        $data = [
            'firstName' => 'Vladimir',
            'lastName' => 'Proshkin'
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals('Vladimir', $record);
        Assert::assertEquals(['lastName' => 'Proshkin'], $remainder);
    }

    public function testSelectFieldsSplitter()
    {
        $splitter = $this->selectFieldsSplitter('firstName', 'lastName');
        $data = [
            'firstName' => 'Vladimir',
            'lastName' => 'Proshkin',
            'city' => 'Voronezh'
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals(['firstName' => 'Vladimir', 'lastName' => 'Proshkin'], $record);
        Assert::assertEquals(['city' => 'Voronezh'], $remainder);
    }

    public function testMapSplitter()
    {
        $splitter = $this->mapSplitter('city', 'country');
        $data = [
            'city' => 'Vladimir',
            'country' => 'Russia',
            'population' => 500000
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals(['Vladimir' => 'Russia'], $record);
        Assert::assertEquals(['population' => 500000], $remainder);
    }

    public function testSelectByPrefixSplitter()
    {
        $splitter = $this->selectByPrefixSplitter('user_');
        $data = [
            'city' => 'Vladimir',
            'user_firstName' => 'Roman',
            'user_lastName' => 'Shishkin'
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals(['firstName' => 'Roman', 'lastName' => 'Shishkin'], $record);
        Assert::assertEquals(['city' => 'Vladimir'], $remainder);
    }

    public function testSelectAllSplitter()
    {
        $splitter = $this->selectAllSplitter();
        $data = [
            'city' => 'Tambov',
            'country' => 'Russia'
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals($data, $record);
        Assert::assertEmpty($remainder);
    }

    public function testCoalesceSplitter()
    {
        $splitter = $this->coalesceSplitter('a', 'b', 'c');
        $data = [
            'a' => null,
            'b' => 2,
            'c' => null,
            'd' => 4
        ];

        list($record, $remainder) = $splitter($data);
        Assert::assertEquals(2, $record);
        Assert::assertEquals(['d' => 4], $remainder);
    }
}
