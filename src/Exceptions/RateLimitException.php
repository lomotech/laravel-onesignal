<?php

namespace Berkayk\OneSignal\Exceptions;

class RateLimitException extends OneSignalException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        public readonly int $retryAfter = 0,
        int $code = 429,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
