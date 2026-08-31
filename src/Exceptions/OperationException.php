<?php

namespace PrasadChinwal\Box\Exceptions;

class OperationException extends BoxException
{
    public static function fromThrowable(string $message, \Throwable $previous): self
    {
        return new self($message, previous: $previous);
    }
}
