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
    private $pool;

    /**
     * @var int
     */
    private $size;

    /**
     * @param callable():T $connector
     */
    public function __construct(callable $connector)
    {
        $this->connector = $connector;
        $this->logger = new NullLogger;
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
     * @return void
     */
    public function push($connection)
    {
        $this->size++;
        $this->logger->debug(sprintf('Releasing connection back to pool (%d connections)', $this->size));
        $this->pool->push($connection);
    }
}
