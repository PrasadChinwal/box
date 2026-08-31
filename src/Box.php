<?php

namespace PrasadChinwal\Box;

use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use PrasadChinwal\Box\Exceptions\ApiException;
use PrasadChinwal\Box\Exceptions\AuthenticationException;
use PrasadChinwal\Box\Exceptions\BoxException;
use PrasadChinwal\Box\Exceptions\ConfigurationException;
use PrasadChinwal\Box\File\BoxFile;
use PrasadChinwal\Box\Folder\BoxFolder;

class Box
{
    protected string $authenticationUrl = 'https://api.box.com/oauth2/token';

    protected string $accessToken;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->bootAccessToken();
    }

    /**
     * @throws Exception
     */
    protected function bootAccessToken(): void
    {
        $cachedToken = Cache::get($this->getTokenCacheKey());

        if (is_array($cachedToken) && $this->tokenIsValid($cachedToken)) {
            $this->setAccessToken($cachedToken['access_token']);

            return;
        }

        $this->requestToken();
    }

    /**
     * @throws Exception
     */
    protected function requestToken(): Box
    {
        $authMethod = (string) $this->getConfig('auth_method', 'app_token');

        match ($authMethod) {
            'app_token' => $response = $this->authRequest()
                ->post($this->authenticationUrl, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->getSignedClaims(),
                    'client_id' => $this->getConfig('client_id'),
                    'client_secret' => $this->getConfig('client_secret'),
                ]),
            'client_credentials' => $response = $this->authRequest()
                ->post($this->authenticationUrl, [
                    'grant_type' => 'client_credentials',
                    'box_subject_type' => 'enterprise',
                    'box_subject_id' => $this->getConfig('enterprise_id'),
                    'client_id' => $this->getConfig('client_id'),
                    'client_secret' => $this->getConfig('client_secret'),
                ]),
            default => throw ConfigurationException::unsupportedAuthMethod($authMethod)
        };

        $this->ensureStatus($response, 200, 'Unable to authenticate with Box.');

        $token = $response->collect()->get('access_token');
        $expiresIn = (int) $response->collect()->get('expires_in', 0);
        $expiresAt = time() + max($expiresIn - $this->getTokenExpiryBuffer(), 0);

        if (empty($token)) {
            throw AuthenticationException::missingAccessToken();
        }

        $this->setAccessToken($token);
        Cache::put($this->getTokenCacheKey(), [
            'access_token' => $token,
            'expires_at' => $expiresAt,
        ], max($expiresAt - time(), 1));

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function getSignedClaims(): string
    {
        return JWT::encode($this->getClaims(), $this->generateKey(), 'RS512');
    }

    /**
     * Returns the claims. Also referred to as payload.
     *
     * @throws Exception
     */
    protected function getClaims(): array
    {
        return [
            'iss' => $this->getConfig('client_id'),
            'sub' => $this->getConfig('enterprise_id'),
            'box_sub_type' => 'enterprise',
            'aud' => $this->authenticationUrl,
            'jti' => base64_encode(random_bytes(64)),
            'exp' => time() + 60,
            'kid' => $this->getConfig('public_key_id'),
        ];
    }

    /**
     * @throws Exception
     */
    protected function generateKey(): OpenSSLAsymmetricKey|bool
    {
        $password = $this->getConfig('passphrase');

        if (! File::exists($this->getConfig('private_key')) || empty($password)) {
            throw ConfigurationException::missingPrivateKey();
        }

        $privateKey = File::get($this->getConfig('private_key'));

        return openssl_pkey_get_private($privateKey, $password);
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return config("box.$key", $default);
    }

    protected function getTokenCacheKey(): string
    {
        return (string) $this->getConfig('token_cache_key', 'box.access_token');
    }

    protected function getTokenExpiryBuffer(): int
    {
        return (int) $this->getConfig('token_expiry_buffer', 60);
    }

    protected function tokenIsValid(array $cachedToken): bool
    {
        return ! empty($cachedToken['access_token'])
            && isset($cachedToken['expires_at'])
            && (int) $cachedToken['expires_at'] > time();
    }

    protected function authRequest(array $options = []): PendingRequest
    {
        return $this->configureRequest(Http::asForm(), $options);
    }

    protected function boxRequest(array $options = []): PendingRequest
    {
        return $this->configureRequest(
            Http::withToken($this->getAccessToken())->acceptJson(),
            $options
        );
    }

    protected function jsonRequest(array $options = []): PendingRequest
    {
        return $this->boxRequest($options)->asJson();
    }

    protected function formRequest(array $options = []): PendingRequest
    {
        return $this->boxRequest($options)->asForm();
    }

    protected function multipartRequest(array $options = []): PendingRequest
    {
        return $this->boxRequest($options)->asMultipart();
    }

    protected function configureRequest(PendingRequest $request, array $options = []): PendingRequest
    {
        $retryTimes = (int) $this->getConfig('request_retry_times', 0);
        $retrySleep = (int) $this->getConfig('request_retry_sleep', 100);

        $request = $request->timeout((int) $this->getConfig('request_timeout', 30));

        if ($retryTimes > 0) {
            $request = $request->retry($retryTimes, $retrySleep, throw: false);
        }

        if ($options !== []) {
            $request = $request->withOptions($options);
        }

        return $request;
    }

    protected function ensureStatus(HttpResponse $response, int|array $expectedStatuses, string $message): HttpResponse
    {
        $expectedStatuses = (array) $expectedStatuses;

        if (in_array($response->status(), $expectedStatuses, true)) {
            return $response;
        }

        throw $this->toBoxException($message, $response);
    }

    protected function ensureSuccessful(HttpResponse $response, string $message): HttpResponse
    {
        if ($response->successful()) {
            return $response;
        }

        throw $this->toBoxException($message, $response);
    }

    protected function toBoxException(string $message, HttpResponse $response): BoxException
    {
        return match ($response->status()) {
            401, 403 => AuthenticationException::fromResponse($message, $response),
            404 => \PrasadChinwal\Box\Exceptions\ResourceNotFoundException::fromResponse($message, $response),
            default => ApiException::fromResponse($message, $response),
        };
    }

    /**
     * Returns accessToken.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Sets the accessToken.
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @throws Exception
     */
    public function file(): BoxFile
    {
        return new BoxFile();
    }

    /**
     * @throws Exception
     */
    public function folder(): BoxFolder
    {
        return new BoxFolder();
    }

    /**
     * @throws Exception
     */
    public function user(): BoxUser
    {
        return new BoxUser();
    }

    /**
     * @throws Exception
     */
    public function collaboration(): BoxCollaboration
    {
        return new BoxCollaboration();
    }
}
