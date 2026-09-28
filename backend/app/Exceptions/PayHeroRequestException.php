<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class PayHeroRequestException extends RuntimeException
{
    /** @param array<string, mixed> $responseData */
    public function __construct(
        string $message,
        public readonly bool $outcomeUnknown = false,
        public readonly ?int $httpStatus = null,
        public readonly array $responseData = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
