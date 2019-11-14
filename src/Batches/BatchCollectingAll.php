<?php

namespace Hamlet\Database\Batches;

use Hamlet\Database\Processing\Collector;

/**
 * @extends Batch<Collector>
 */
class BatchCollectingAll extends Batch
{
    /**
     * @param Collector $collector
     * @return array
     */
    public function apply($collector)
    {
        return $collector->collectAll();
    }
}
