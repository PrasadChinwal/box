<?php

namespace PrasadChinwal\Box\Exceptions;

class ConfigurationException extends BoxException
{
    public static function unsupportedAuthMethod(string $method): self
    {
        return new self("Unsupported Box auth method [{$method}].");
    }

    public static function missingPrivateKey(): self
    {
        return new self('Could not find private-key.pem file in the base directory.');
    }
}
