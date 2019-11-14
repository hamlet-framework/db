<?php

namespace Hamlet\Database\Batches;

use Hamlet\Database\Processing\Collector;

/**
 * @extends Batch<Collector>
 */
final class BatchCollectingHeads extends Batch
{
    /**
     * @param Collector $collector
     * @return mixed
     */
    public function apply($collector)
    {
        return $collector->collectHead();
    }
}
