<?php declare(strict_types=1);

namespace Hamlet\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplQueue;

/**
 * @template T
 * @implements ConnectionPool<T>
 */
class SimpleConnectionPool implements ConnectionPool
{
    private LoggerInterface $logger;

    /**
     * @var SplQueue<T>
     */
    private SplQueue $pool;

    private int $size;

    /**
     * @param callable():T $connector
     */
    public function __construct(private readonly mixed $connector)
    {
        $this->logger = new NullLogger;
        $this->pool = new SplQueue;
        $this->size = 0;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return T
     */
    public function pop(): mixed
    {
        if ($this->size > 0) {
            $this->size--;
            $this->logger->debug(sprintf('Fetching connection from pool (%d connections left in pool)', $this->size));
            $connection = $this->pool->pop();
        } else {
            $this->logger->debug('Opening new connection');
            $connection = ($this->connector)();
        }
        return $connection;
    }

    /**
     * @param T $connection
     */
    public function push($connection): void
    {
        $this->size++;
        $this->logger->debug(sprintf('Releasing connection back to pool (%d connections)', $this->size));
        $this->pool->push($connection);
    }
}
