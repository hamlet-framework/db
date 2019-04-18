Validation
===

When selecting a list of objects from the database it's often convenient to index the resulting collection by respective object's primary key

    +----+--------+----------+-----------+
    | id |  name  | latitude | longitude |
    +----+--------+----------+-----------+
    |  1 |  Ivan  |     10.1 |    -12.33 |
    |  2 |  Petr  |     12.2 |    101.21 |
    +----+--------+----------+-----------+

The following processor will do just that

```php
$query = '
    SELECT id,
           id        AS user_id
           name      AS user_name,
           latitude  AS user_latitude,
           longitude AS user_longitude
      FROM users
';
$processor = $database->prepare($query);
$processor->processAll()
    ->selectByPrefix('user_')->castInto(User::class, 'user')
    ->map('id', 'user')->flatten()
    ->collectAll();
```

There are two "flaky" aspects of this processing code. Firstly, static analyser 
most likely won't be able to guess the result of the `collectAll()`. Secondly, at one point there will be code
down the stream that relies on the fact that the keys are exactly the same as the respective objects' IDs, i.e.
key with value 8331 must point at the user with ID #8331.

To add this kind of assertions use methods `assertType` and `assertForEach`. The processor code will become

```php
$query = '
    SELECT id,
           id        AS user_id
           name      AS user_name,
           latitude  AS user_latitude,
           longitude AS user_longitude
      FROM users
';
$processor = $database->prepare($query);
$processor->processAll()
    ->selectByPrefix('user_')->castInto(User::class, 'user')
    ->map('id', 'user')->flatten()
    ->assertType(_int(), _class(User::class))
    ->assertForEach(function ($key, $value) {
        return (($value instanceof User) && ($key == $value->id());
    })
    ->collectAll();
``` 

Both checks are only enabled when assertions are enabled.

To read more about type specifications used in `assertType` see: https://github.com/hamlet-framework/cast

