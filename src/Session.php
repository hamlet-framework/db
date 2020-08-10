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
    /**
     * @var mixed
     * @psalm-var T
     */
    protected $handle;

    /**
     * @var bool
     */
    protected $transactionStarted;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param mixed $handle
     * @psalm-param T $handle
     */
    protected function __construct($handle)
    {
        $this->handle = $handle;
        $this->transactionStarted = false;
        $this->logger = new NullLogger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $query
     * @return Procedure
     */
    abstract public function prepare(string $query): Procedure;

    /**
     * @template Q
     * @param callable $callable
     * @psalm-param callable():Q $callable
     * @return mixed
     * @psalm-return Q
     */
    public function withTransaction(callable $callable)
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
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function startTransaction($connection);

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function commit($connection);

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function rollback($connection);
}
