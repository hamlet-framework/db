<?php declare(strict_types=1);

namespace Hamlet\Database;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @template T
 */
interface ConnectionPool extends LoggerAwareInterface
{
    public function setLogger(LoggerInterface $logger): void;

    /**
     * @return T
     */
    public function pop(): mixed;

    /**
     * @param T $connection
     */
    public function push(mixed $connection): void;
}
