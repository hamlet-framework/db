<?php

namespace Hamlet\Database\Processing;

use Hamlet\Cast\Type;
use Hamlet\Database\Entity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function Hamlet\Cast\_class;
use function Hamlet\Cast\_int;
use function Hamlet\Cast\_string;

class Phone
{
    public $name, $phone;
}

class Address
{
    /** @var string */
    public $street;
    /** @var int */
    public $number;
}

class AddressBookEntry
{
    /** @var string */
    public $name;
    /** @var Address[] */
    public $addresses;
}

class PhoneEntity implements Entity
{
    private $name, $phone;

    public function name(): string
    {
        return $this->name;
    }

    public function phone(): string
    {
        return $this->phone;
    }
}

class BatchProcessorTest extends TestCase
{
    protected function streamingMode(): bool
    {
        return false;
    }

    private function phones()
    {
        $rows = [
            [
                'name' => 'John',
                'phone' => '123'
            ],
            [
                'name' => 'John',
                'phone' => '785'
            ],
            [
                'name' => 'Bill',
                'phone' => '12333'
            ]
        ];
        foreach ($rows as $index => $row) {
            yield $index => $row;
        }
    }

    private function addresses()
    {
        $rows = [
            [
                'name' => 'John',
                'address_street' => 'Lenin Street',
                'address_number' => 1917
            ],
            [
                'name' => 'John',
                'address_street' => 'Pushkin Square',
                'address_number' => 1
            ],
            [
                'name' => 'John',
                'address_street' => null,
                'address_number' => null
            ],
            [
                'name' => 'Anatoly',
                'address_street' => 'Tolstoy lane',
                'address_number' => 1812
            ]
        ];
        foreach ($rows as $index => $row) {
            yield $index => $row;
        }
    }

    private function cities()
    {
        $rows = [
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Geelong'
            ],
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Melbourne'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Balakovo'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Saratov'
            ]
        ];
        foreach ($rows as $index => $row) {
            yield $index => $row;
        };
    }

    private function locations()
    {
        $rows = [
            [
                'country' => null,
                'state' => 'Victoria',
                'city' => 'Geelong'
            ],
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Melbourne'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Balakovo'
            ],
            [
                'country' => null,
                'state' => null,
                'city' => 'Saratov'
            ]
        ];
        foreach ($rows as $index => $row) {
            yield $index => $row;
        }
    }

    public function testFieldExtractor()
    {
        $collection = (new Selector($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertEquals('John', $collection[0]['name']);
        Assert::assertEquals(2, count($collection[0]['phones']));
    }

    public function testCollectToMap()
    {
        $collection = (new Selector($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->map('name', 'phones')->flatten()
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertArrayHasKey('Bill', $collection);
    }

    public function testPrefixExtractor()
    {
        $collection = (new Selector($this->addresses(), $this->streamingMode()))
            ->selectByPrefix('address_')->groupInto('addresses')
            ->map('name', 'addresses')->flatten()
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertEquals(1812, $collection['Anatoly'][0]['number']);
    }

    public function testNestedGroups()
    {
        $collection = (new Selector($this->cities(), $this->streamingMode()))
            ->selectValue('city')->groupInto('cities')
            ->map('state', 'cities')->flattenInto('states')
            ->map('country', 'states')->flatten()
            ->collectAll();

        Assert::assertEquals('Balakovo', $collection['Russia']['Saratovskaya Oblast'][0]);
    }

    public function testCollectTypedList()
    {
        $collection = (new Selector($this->phones(), $this->streamingMode()))
            ->selectAll()->cast(Phone::class)
            ->collectAll();

        Assert::assertInstanceOf(Phone::class, $collection[0]);
    }

    public function testCollectTypedListOfMappedEntities()
    {
        $collection = (new Selector($this->phones(), $this->streamingMode()))
            ->selectAll()->cast(PhoneEntity::class)
            ->collectAll();

        Assert::assertInstanceOf(PhoneEntity::class, $collection[0]);
    }

    public function testCollectNestedTypedList()
    {
        $collection = (new Selector($this->addresses(), $this->streamingMode()))
            ->selectByPrefix('address_')->castInto(Address::class, 'address')
            ->selectValue('address')->groupInto('addresses')
            ->selectAll()->cast(AddressBookEntry::class)
            ->assertType(_int(), _class(AddressBookEntry::class))
            ->collectAll();

        Assert::assertInstanceOf(AddressBookEntry::class, $collection[0]);
        Assert::assertInstanceOf(Address::class, $collection[0]->addresses[1]);
    }

    public function testCollate()
    {
        $collection = (new Selector($this->locations(), $this->streamingMode()))
            ->coalesceAll()
            ->assertType(_int(), _string())
            ->assertForEach(function ($id, $name) {
                return !empty($name);
            })
            ->collectAll();

        Assert::assertEquals('Victoria', $collection[0]);
        Assert::assertEquals('Australia', $collection[1]);
        Assert::assertEquals('Russia', $collection[2]);
        Assert::assertEquals('Saratov', $collection[3]);
    }

    public function testCollator()
    {
        $collection = (new Selector($this->locations(), $this->streamingMode()))
            ->coalesce('state', 'city')->name('details')
            ->map('country', 'details')->flatten()
            ->collectAll();

        Assert::assertEquals('Saratovskaya Oblast', $collection['Russia']);
    }

    public function testGroupIndices()
    {
        $collection = (new Selector($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->assertType(
                _int(),
                Type::of('array{name:string,phones:array<int,string>}')
            )
            ->collectAll();

        Assert::assertEquals([0, 2], array_keys($collection));
    }

    public function testMapIndices()
    {
        $collection = (new Selector($this->cities(), $this->streamingMode()))
            ->map('city', 'state')->flattenInto('cities')
            ->collectAll();

        Assert::assertEquals([0, 2], array_keys($collection));
    }

    public function testIterator()
    {
        $iterator = (new Selector($this->phones(), $this->streamingMode()))
            ->map('phone', 'name')->flatten()
            ->iterator();

        $phones = [];
        foreach ($iterator as $phone => $name) {
            $phones[] = [
                'name' => $name,
                'phone' => $phone
            ];
        }

        Assert::assertEquals(iterator_to_array($this->phones()), $phones);
    }
}
