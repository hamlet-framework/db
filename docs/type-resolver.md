# Type resolvers

Hamlet DB supports tagged inheritance, by using a special method `__resolveType`. 

As an example take a table called `users` with each user having an ID, a name and optionally a latitude and a longitude.
Each row can be mapped into two different entities based on availability of geo location. 

First step is to create the following type hierarchy

```php
class User implements Entity
{
    protected $id, $name, $latitude, $longitude;
    
    public function id(): int 
    {
        return $this->id;
    }
    
    public function name(): string 
    {
        return $this->name;
    }
}

class UserWithLocation extends User
{
    public function location(): Coordinates 
    {
        return new Coordinates($this->latitude, $this->longitude);
    }
}
```

In order to differentiate between these two types Hamlet processor uses a methods called `__resolveType` placed in the root of the hierarchy:

```php
class User implements Entity 
{
    ...
    
    public static function __resolveType(array $properties): string
    {
        if (isset($properties['latitude']) && isset($properties['longitude'])) {
            return UserWithLocation::class;
        }
        return User::class;
    }
}
```

Running the following processor

```php
...->selectAll()->cast(User::class)->collectAll();
```

over the result set

    +----+--------+----------+-----------+
    | id |  name  | latitude | longitude |
    +----+--------+----------+-----------+
    |  1 |  Ivan  |     10.1 |    -12.33 |
    |  2 |  Petr  |     null |      null |
    +----+--------+----------+-----------+
    
returns two objects, first object of type `UserWithLocation` and the second object will be of type `User`.

Additionally, when running the processor 

```php
...->selectAll()->cast(UserWithLocation::class)->collectAll();
```

over the same result set, an exception with be thrown, indicating that one of the types is not a subsclass of `UserWithLocation`.
