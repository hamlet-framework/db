<?php

namespace Hamlet\Database;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @template T
 */
class ConnectionPool implements LoggerAwareInterface
{
    /**
     * @var callable():T
     */
    private $connector;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<T>
     */
    private $pool = [];

    /**
     * @param callable():T $connector
     */
    public function __construct(callable $connector)
    {
        $this->connector = $connector;
        $this->logger = new NullLogger();
    }


    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return T
     */
    public function pop()
    {
        $connection = array_pop($this->pool);
        if ($connection !== null) {
            $this->logger->debug('Fetching connection from pool (' . count($this->pool) . ' connections left in pool)');
            return $connection;
        }
        $this->logger->debug('Opening new connection');
        return ($this->connector)();
    }

    /**
     * @param T $connection
     * @return void
     */
    public function push($connection)
    {
        $this->logger->debug('Releasing connection back to pool (' . count($this->pool) . ' connections)');
        array_push($this->pool, $connection);
    }
}
