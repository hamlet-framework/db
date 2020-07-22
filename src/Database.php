<?php declare(strict_types=1);

namespace Hamlet\Database;

use Exception;
use Hamlet\Database\Processing\BatchProcessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @template T
 */
abstract class Database implements LoggerAwareInterface
{
    /**
     * @var ConnectionPool<T>
     */
    protected $pool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ConnectionPool<T> $pool
     */
    protected function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
        $this->logger = new NullLogger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->pool->setLogger($logger);
    }

    /**
     * @param T $handle
     * @return Session<T>
     */
    abstract protected function createSession($handle): Session;

    /**
     * @template Q
     * @param callable(Session):Q $callable
     * @return Q
     */
    public function withSession(callable $callable)
    {
        $handle = $this->pool->pop();
        $session = $this->createSession($handle);
        try {
            $result = $callable($session);
            return $result;
        } catch (DatabaseException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DatabaseException('Failed to execute statement', 0, $e);
        } finally {
            $this->pool->push($handle);
        }
    }

    /**
     * @template K
     * @template Q
     * @param array<K,callable(Session):Q> $callables
     * @return array<K,Q>
     */
    public function withSessions(array $callables)
    {
        $result = [];
        foreach ($callables as $key => $callable) {
            $result[$key] = $this->withSession($callable);
        }
        return $result;
    }

    /**
     * @param array<callable(Session):Procedure> $generators
     * @return MultiProcedureContext
     */
    public function prepareMultiple(array $generators)
    {
        return new SimpleMultiProcedureContext($this, $generators);
    }
}
