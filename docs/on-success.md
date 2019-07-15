On Success Handlers 
===

The `onSuccess` handlers help with the following pattern. Let's see two functions (in pseudocode)

```
function a() {
    withTransaction {
        b()
        ...
    }
    updateLastActionTime()
}

function b() {
    withTransaction {
        ...
    }
    updateLastActionTime()
}
```

Ideally we'd like to execute `updateLastActionTime` only once and only at the commit of the outer transaction. 
The `onSuccess` handlers allow to achieve just that:

```
class C {
    
    private $database;
    private $logger;
    
    public function a() {
        $onSuccess = [
            'log' => [$this, 'updateLastActionTime'],
            'message' => [$this, 'sendNotification']
        ];
        $this->database->withTransaction() {
            ...
            $this->b();
        }, $onSuccess);
    }
    
    public function b() {
        $onSuccess = [
            'log' => [$this, 'updateLastActionTime']
        ];
        $this->database->withTransaction() {
            ...
            $this->b();
        }, $onSuccess);
    }
    
    public function updateLastActionTime() {
        ...
    }
    
    public function sendNotification() {
        ...
    }
}
```

Now it doesn't matter if you call `C::a` or `C::b`, 
the last action time will be updated only once and only at the successful execution of the complete transaction. 
The method `C::sendNotification` will only be called when transaction in `C::a` succeeds.

## Handlers factory

It's possible to simplify the logic a bit by extracting creation of handlers into static factory methods.

```
final class Handlers {

    public static function updateLastActionTime() {
        return [
            'updateLastActionTime' => function () { ... }
        ]
    }
    
    public static function sendNotification(int $userId) {
        return [
            'sendNotification-' . $userId => function () use ($userId) { ... }
        ]
    }
}

class C {
    
    public function a() {
        $this->database->withTransaction() {
            ...
            $this->b();
        }, Handlers::updateLastActionTime() + Handlers::sendNotification(1));
    }
    
    public function b() {
        $this->database->withTransaction() {
            ...
            $this->b();
        }, Handlers::updateLastActionTime());
    }
}

``` 
