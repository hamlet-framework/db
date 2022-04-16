<?php declare(strict_types=1);

namespace Hamlet\Database;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @template T
 */
abstract class Session implements LoggerAwareInterface
{
    protected bool $transactionStarted;

    protected LoggerInterface $logger;

    /**
     * @param T $handle
     */
    protected function __construct(protected readonly mixed $handle)
    {
        $this->transactionStarted = false;
        $this->logger = new NullLogger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    abstract public function prepare(string $query): Procedure;

    /**
     * @template Q
     * @param callable():Q $callable
     * @return Q
     */
    public function withTransaction(callable $callable): mixed
    {
        $newTransaction = !$this->transactionStarted;
        try {
            if ($newTransaction) {
                $this->startTransaction($this->handle);
                $this->transactionStarted = true;
            } else {
                $this->logger->debug('Transaction already started');
            }
            $result = $callable();
            if ($newTransaction) {
                $this->commit($this->handle);
            }
            return $result;
        } catch (DatabaseException $e) {
            $this->rollback($this->handle);
            throw $e;
        } catch (Exception $e) {
            $this->rollback($this->handle);
            throw new DatabaseException('Failed to execute statement', 0, $e);
        } finally {
            if ($newTransaction) {
                $this->transactionStarted = false;
            }
        }
    }

    /**
     * @param T $connection
     */
    abstract protected function startTransaction(mixed $connection): void;

    /**
     * @param T $connection
     */
    abstract protected function commit(mixed $connection): void;

    /**
     * @param T $connection
     */
    abstract protected function rollback(mixed $connection): void;
}
