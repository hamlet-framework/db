Executing multiple statements without releasing connection to the pool
===

```
$procedure = $database->prepare(...);
$names = [];
$procedure->withSameConnection(function () use ($ids, &names) {
    foreach ($ids as $id) {
        $procedure->bindInteger($id);
        $names[] = $procedure->processOne()->collateAll();
    }
});
```
