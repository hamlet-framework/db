<?php

class Collection
{
    private $records;

    public function __construct(Generator $records)
    {
        $this->records = $records;
    }

    public function output()
    {
        foreach ($this->records as &$record) {
            echo $record . PHP_EOL;
        }
    }
}

$generator = function () {
    yield from [1, 2, 3, 4];
};

$collection = new Collection($generator());
$collection->output();
