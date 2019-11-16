<?php

namespace Hamlet\Database;

class MultiProcedureContext
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var callable[]
     * @psalm-var array<callable(Session):Procedure>
     */
    private $generators;

    /**
     * @param Database $database
     * @param callable[] $generators
     * @psalm-var array<callable(Session):Procedure> $generators
     */
    public function __construct(Database $database, array $generators)
    {
        $this->database = $database;
        $this->generators = $generators;
    }

    /**
     * @template T
     * @param callable $processor
     * @psalm-param callable(Procedure):T $processor
     * @return array
     * @psalm-return array<T>
     * @psalm-suppress MissingClosureReturnType
     */
    public function forEach(callable $processor)
    {
        $callables = [];
        foreach ($this->generators as $generator) {
            $callables[] = function (Session $session) use ($generator, $processor) {
                return $processor($generator($session));
            };
        }
        return $this->database->withSessions($callables);
    }
}
