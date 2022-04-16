<?php declare(strict_types=1);

namespace Hamlet\Database;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @template T
 */
abstract class Database implements LoggerAwareInterface
{
    protected LoggerInterface $logger;

    /**
     * @param ConnectionPool<T> $pool
     */
    protected function __construct(protected readonly ConnectionPool $pool)
    {
        $this->logger = new NullLogger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->pool->setLogger($logger);
    }

    /**
     * @param T $handle
     * @return Session<T>
     */
    abstract protected function createSession(mixed $handle): Session;

    /**
     * @template Q
     * @param callable(Session):Q $callable
     * @return Q
     */
    public function withSession(callable $callable): mixed
    {
        $handle = $this->pool->pop();
        $session = $this->createSession($handle);
        try {
            return $callable($session);
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
     * @psalm-suppress MixedAssignment
     * @psalm-suppress UndefinedClass
     */
    public function withSessions(array $callables): array
    {
        $results = [];
        if (class_exists('\parallel\Runtime')) {
            $runtime = new \parallel\Runtime();
            $futures = [];
            foreach ($callables as $key => $callable) {
                $futures[$key] = $runtime->run(function () use ($callable) {
                    return $this->withSession($callable);
                });
            }
            foreach ($futures as $key => $future) {
                $results[$key] = $future;
            }
        } else {
            foreach ($callables as $key => $callable) {
                $results[$key] = $this->withSession($callable);
            }
        }
        return $results;
    }
}
