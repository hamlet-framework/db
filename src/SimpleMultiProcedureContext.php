<?php

namespace Hamlet\Database;

class SimpleMultiProcedureContext implements MultiProcedureContext
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var array<callable(Session):Procedure>
     */
    private $generators;

    /**
     * @param Database $database
     * @param array<callable(Session):Procedure> $generators
     */
    public function __construct(Database $database, array $generators)
    {
        $this->database = $database;
        $this->generators = $generators;
    }

    /**
     * @template T
     * @param callable(Procedure):T $processor
     * @return array<T>
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
