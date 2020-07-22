<?php declare(strict_types=1);

namespace Hamlet\Database;

interface MultiProcedureContext
{
    /**
     * @template T
     * @param callable(Procedure):T $processor
     * @return array<T>
     */
    public function forEach(callable $processor);
}
