# Type resolvers

Hamlet DB supports tagged inheritance, by using a special method `__resolveType`. 

Let's take an example of a `users` table with each user having an ID, a name and optionally a latitude and a longitude.
We can map each row into two different entities based on availability of geo location. 

First step is to create the following type hierarchy

```php
class User implements Entity
{
    protected $id, $name, $latitude, $longitude;
    
    public function id(): int {...}
    
    public function name(): string {...}
}

class UserWithLocation extends User
{
    public function location(): Coordinates {...}
}
```

To do so we need to explain to Hamlet processor how to differentiate between a `User` and `UserWithLocation`. 
And for this we need to create a public static method `__resolveType` in the root class of hierarchy.
 
In our case:

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

After that if you try and run the processor

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
    
The `collectAll` will return two objects, first object will be of type `UserWithLocation` and the second object will be of type `User`.

Interestingly enough, if you try and run 

```php
...->selectAll()->cast(UserWithLocation::class)->collectAll();
```

over the same result set, an exception with be thrown, indicating that one of the types is not a subsclass of `UserWithLocation`.
