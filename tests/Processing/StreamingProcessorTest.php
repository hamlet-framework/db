<?php

namespace Hamlet\Database\Processing;

class StreamingProcessorTest extends BatchProcessorTest
{
    protected function streamingMode(): bool
    {
        return true;
    }



}
