<?php

namespace Hamlet\Database;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplQueue;

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
     * @var SplQueue<T>
     */
    private $pool = [];

    /** @var int */
    private $size;

    /**
     * @param callable():T $connector
     */
    public function __construct(callable $connector)
    {
        $this->connector = $connector;
        $this->logger = new NullLogger();
        $this->pool = new SplQueue;
        $this->size = 0;
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
        if ($this->size > 1) {
            $this->logger->debug('Fetching connection from pool (' . count($this->pool) . ' connections left in pool)');
            $this->size--;
            $connection = $this->pool->pop();
        } else {
            $this->logger->debug('Opening new connection');
            $connection = ($this->connector)();
        }
        return $connection;
    }

    /**
     * @param T $connection
     * @return void
     */
    public function push($connection)
    {
        $this->logger->debug('Releasing connection back to pool (' . count($this->pool) . ' connections)');
        $this->size++;
        $this->pool->push($connection);
    }
}
