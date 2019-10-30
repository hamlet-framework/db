<?php

namespace Hamlet\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplQueue;

/**
 * @template T
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var callable
     * @psalm-var callable():T
     */
    private $connector;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SplQueue
     * @psalm-var SplQueue<T>
     */
    private $pool;

    /**
     * @var int
     */
    private $size;

    /**
     * @param callable $connector
     * @psalm-param callable():T $connector
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
     * @return mixed
     * @psalm-return T
     */
    public function pop()
    {
        if ($this->size > 0) {
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
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    public function push($connection)
    {
        $this->logger->debug('Releasing connection back to pool (' . count($this->pool) . ' connections)');
        $this->size++;
        $this->pool->push($connection);
    }
}
