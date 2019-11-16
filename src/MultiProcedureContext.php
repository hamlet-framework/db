<?php

namespace Hamlet\Database;

interface MultiProcedureContext
{
    /**
     * @template T
     * @param callable $processor
     * @psalm-param callable(Procedure):T $processor
     * @return array
     * @psalm-return array<T>
     * @psalm-suppress MissingClosureReturnType
     */
    public function forEach(callable $processor);
}
