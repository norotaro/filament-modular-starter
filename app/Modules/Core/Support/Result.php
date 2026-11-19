<?php

namespace App\Modules\Core\Support;

use InvalidArgumentException;

/**
 * @template-covariant T
 */
final class Result
{
    /**
     * @param  T|null  $value
     */
    private function __construct(
        private readonly bool $isSuccess,
        private readonly mixed $value,
        private readonly ?string $errorMessage,
    ) {}

    /**
     * @template U
     *
     * @param  U  $value
     * @return self<U>
     */
    public static function success(mixed $value): self
    {
        return new self(true, $value, null);
    }

    /**
     * @return self<never>
     */
    public static function failure(string $errorMessage): self
    {
        return new self(false, null, $errorMessage);
    }

    /**
     * Check if the operation was completed successfully.
     *
     * @phpstan-assert-if-true !null $this->value()
     * @phpstan-assert-if-true null $this->errorMessage()
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * Check if the operation failed to complete.
     *
     * @phpstan-assert-if-true null $this->value()
     * @phpstan-assert-if-true !null $this->errorMessage()
     */
    public function isFailure(): bool
    {
        return ! $this->isSuccess;
    }

    /**
     * @return ?T
     */
    public function value(): mixed
    {
        if ($this->isFailure()) {
            throw new InvalidArgumentException('Cannot retrieve value from a failed result.');
        }

        return $this->value;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
