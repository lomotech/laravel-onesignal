<?php

namespace Berkayk\OneSignal\Exceptions;

use RuntimeException;

class OneSignalException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?array $errors = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
