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

    private function empty()
    {
        return yield from [];
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
        $collection = (new SplitContext($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->collectAll();

        $this->assertCount(2, $collection);
        $this->assertEquals('John', $collection[0]['name']);
        $this->assertCount(2, $collection[0]['phones']);
    }

    public function testCollectToMap()
    {
        $collection = (new SplitContext($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->map('name', 'phones')->flatten()
            ->collectAll();

        $this->assertCount(2, $collection);
        $this->assertArrayHasKey('John', $collection);
        $this->assertArrayHasKey('Bill', $collection);
    }

    public function testGroup()
    {
        $head = (new SplitContext($this->addresses(), $this->streamingMode()))
            ->selectByPrefix('address_')->group()
            ->collectHead();

        $this->assertCount(2, $head);
        $this->assertEquals('Pushkin Square', $head[1]['street']);
    }

    public function testSelectFieldsExplicitly()
    {
        $collection = (new SplitContext($this->addresses(), $this->streamingMode()))
            ->selectFields('address_street', 'address_number')->groupInto('addresses')
            ->map('name', 'addresses')->flatten()
            ->collectAll();

        $this->assertCount(2, $collection);
        $this->assertArrayHasKey('John', $collection);
        $this->assertEquals(1812, $collection['Anatoly'][0]['address_number']);
    }

    public function testPrefixExtractor()
    {
        $collection = (new SplitContext($this->addresses(), $this->streamingMode()))
            ->selectByPrefix('address_')->groupInto('addresses')
            ->map('name', 'addresses')->flatten()
            ->collectAll();

        $this->assertCount(2, $collection);
        $this->assertArrayHasKey('John', $collection);
        $this->assertEquals(1812, $collection['Anatoly'][0]['number']);
    }

    public function testNestedGroups()
    {
        $collection = (new SplitContext($this->cities(), $this->streamingMode()))
            ->selectValue('city')->groupInto('cities')
            ->map('state', 'cities')->flattenInto('states')
            ->map('country', 'states')->flatten()
            ->collectAll();

        $this->assertEquals('Balakovo', $collection['Russia']['Saratovskaya Oblast'][0]);
    }

    public function testCollectTypedList()
    {
        $collection = (new SplitContext($this->phones(), $this->streamingMode()))
            ->selectAll()->cast(Phone::class)
            ->collectAll();

        $this->assertInstanceOf(Phone::class, $collection[0]);
    }

    public function testCollectTypedListOfMappedEntities()
    {
        $collection = (new SplitContext($this->phones(), $this->streamingMode()))
            ->selectAll()->cast(PhoneEntity::class)
            ->collectAll();

        $this->assertInstanceOf(PhoneEntity::class, $collection[0]);
    }

    public function testCollectNestedTypedList()
    {
        $collection = (new SplitContext($this->addresses(), $this->streamingMode()))
            ->selectByPrefix('address_')->castInto(Address::class, 'address')
            ->selectValue('address')->groupInto('addresses')
            ->selectAll()->cast(AddressBookEntry::class)
            ->assertType(_int(), _class(AddressBookEntry::class))
            ->collectAll();

        $this->assertInstanceOf(AddressBookEntry::class, $collection[0]);
        $this->assertInstanceOf(Address::class, $collection[0]->addresses[1]);
    }

    public function testCollate()
    {
        $collection = (new SplitContext($this->locations(), $this->streamingMode()))
            ->coalesceAll()
            ->assertType(_int(), _string())
            ->assertForEach(function ($id, $name) {
                return !empty($name);
            })
            ->collectAll();

        $this->assertEquals('Victoria', $collection[0]);
        $this->assertEquals('Australia', $collection[1]);
        $this->assertEquals('Russia', $collection[2]);
        $this->assertEquals('Saratov', $collection[3]);
    }

    public function testCollator()
    {
        $collection = (new SplitContext($this->locations(), $this->streamingMode()))
            ->coalesce('state', 'city')->name('details')
            ->map('country', 'details')->flatten()
            ->collectAll();

        $this->assertEquals('Saratovskaya Oblast', $collection['Russia']);
    }

    public function testGroupIndices()
    {
        $collection = (new SplitContext($this->phones(), $this->streamingMode()))
            ->selectValue('phone')->groupInto('phones')
            ->assertType(
                _int(),
                Type::of('array{name:string,phones:array<int,string>}')
            )
            ->collectAll();

        $this->assertEquals([0, 2], array_keys($collection));
    }

    public function testMapIndices()
    {
        $collection = (new SplitContext($this->cities(), $this->streamingMode()))
            ->map('city', 'state')->flattenInto('cities')
            ->collectAll();

        $this->assertEquals([0, 2], array_keys($collection));
    }

    public function testIterator()
    {
        $iterator = (new SplitContext($this->phones(), $this->streamingMode()))
            ->map('phone', 'name')->flatten()
            ->iterator();

        $phones = [];
        foreach ($iterator as $phone => $name) {
            $phones[] = [
                'name' => $name,
                'phone' => $phone
            ];
        }

        $this->assertEquals(iterator_to_array($this->phones()), $phones);
    }

    public function testCollectingHeadFromEmptyCollection()
    {
        $collection = new Collection($this->empty(), $this->streamingMode());

        $this->assertNull($collection->collectHead());
    }
}
