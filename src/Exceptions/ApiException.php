<?php

namespace PrasadChinwal\Box\Exceptions;

use Illuminate\Http\Client\Response;

class ApiException extends BoxException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $url = null,
        public readonly mixed $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public static function fromResponse(string $message, Response $response): static
    {
        return new static(
            $message,
            $response->status(),
            $response->effectiveUri()?->__toString(),
            $response->json() ?? $response->body()
        );
    }
}
