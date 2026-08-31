<?php

namespace PrasadChinwal\Box\Exceptions;

use Illuminate\Http\Client\Response;

class ResourceNotFoundException extends ApiException
{
    public static function make(string $message, ?Response $response = null): self
    {
        if ($response) {
            return self::fromResponse($message, $response);
        }

        return new self($message, 404);
    }
}
