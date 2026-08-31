<?php

namespace PrasadChinwal\Box\Exceptions;

use Illuminate\Http\Client\Response;

class AuthenticationException extends ApiException
{
    public static function tokenRequestFailed(Response $response): self
    {
        return self::fromResponse('Unable to authenticate with Box.', $response);
    }

    public static function missingAccessToken(): self
    {
        return new self('The Box authentication response did not include an access token.');
    }
}
