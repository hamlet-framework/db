<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Entity;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractUser implements Entity
{
    protected $name;
    protected $latitude;
    protected $longitude;

    public static function __resolveType(array $properties)
    {
        if (isset($properties['latitude']) && isset($properties['longitude'])) {
            return User::class;
        } else {
            return AnonymousUser::class;
        }
    }

    public function name()
    {
        return $this->name;
    }
}

class User extends AbstractUser
{
    public function latitude()
    {
        return $this->latitude;
    }

    public function longitude()
    {
        return $this->longitude;
    }
}

class AnonymousUser extends AbstractUser
{
}

class SuperAnonymousUser extends AnonymousUser
{
}

class RandomClass implements Entity
{
}

class TypeResolutionTest extends TestCase
{
    private function users()
    {
        $rows = [
            [
                'name' => 'Pyotr',
                'latitude' => 12.03,
                'longitude' => -33.9
            ],
            [
                'name' => 'Anfeesa',
                'latitude' => -100.03,
                'longitude' => 19.001
            ],
            [
                'name' => 'Mikhail',
                'latitude' => null,
                'longitude' => null
            ],
            [
                'name' => 'Lena',
                'latitude' => 24.12,
                'longitude' => -13.32
            ],
        ];
        foreach ($rows as $index => $row) {
            yield $index => $row;
        }
    }

    public function testTypeResolver()
    {
        $collection = (new SplitContext($this->users(), false))
            ->selectAll()->cast(AbstractUser::class)
            ->collectAll();

        $this->assertInstanceOf(User::class, $collection[0]);
        $this->assertInstanceOf(User::class, $collection[1]);
        $this->assertInstanceOf(AnonymousUser::class, $collection[2]);
        $this->assertInstanceOf(User::class, $collection[3]);
    }

    public function testTypeResolverThrowsExceptionOnFailedCastExpectations()
    {
        $this->expectException(RuntimeException::class);
        (new SplitContext($this->users(), false))
            ->selectAll()->cast(SuperAnonymousUser::class)
            ->collectAll();
    }

    public function testTypeResolverThrowsExceptionOfUnrelatedClass()
    {
        $this->expectException(RuntimeException::class);
        (new SplitContext($this->users(), false))
            ->selectAll()->cast(RandomClass::class)
            ->collectAll();
    }
}
