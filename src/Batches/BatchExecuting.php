<?php

namespace Hamlet\Database\Batches;

use Hamlet\Database\Procedure;

/**
 * @extends Batch<Procedure>
 */
class BatchExecuting extends Batch
{
    /**
     * @param Procedure $procedure
     * @return void
     */
    public function apply($procedure)
    {
        $procedure->execute();
    }
}
