<?php declare(strict_types=1);

namespace Hamlet\Database;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @template T
 */
interface ConnectionPool extends LoggerAwareInterface
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
     * @param T $connection
     * @return void
     */
    public function push($connection);
}
