<?php

namespace Hamlet\Database;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @template T
 */
interface ConnectionPoolInterface extends LoggerAwareInterface
{
    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @return mixed
     * @psalm-return T
     */
    public function pop();

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    public function push($connection);
}
