<?php declare(strict_types=1);

namespace Hamlet\Database\Traits;

use function is_array;

trait EntityFactoryTrait
{
    /**
     * @param mixed $item
     * @return bool
     * @psalm-assert !null $item
     */
    private function isNull($item): bool
    {
        if (is_array($item)) {
            /**
             * @psalm-suppress MixedAssignment
             */
            foreach ($item as &$value) {
                if (!$this->isNull($value)) {
                    return false;
                }
            }
            return true;
        } else {
            return $item === null;
        }
    }
}
