<?php

namespace PrasadChinwal\Box;

use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
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
        $this->requestToken();
    }

    /**
     * @throws Exception
     */
    protected function requestToken(): Box
    {
        $response = Http::asForm()
            ->post($this->authenticationUrl, [
                'grant_type' => 'client_credentials',
                'box_subject_type' => 'enterprise',
                'box_subject_id' => config('box.enterprise_id'),
                'client_id' => config('box.client_id'),
                'client_secret' => config('box.client_secret'),
            ])
            ->throwUnlessStatus(200);

        $this->setAccessToken($response->collect()->get('access_token'));

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
            'iss' => config('box.client_id'),
            'sub' => config('box.enterprise_id'),
            'box_sub_type' => 'enterprise',
            'aud' => $this->authenticationUrl,
            'jti' => base64_encode(random_bytes(64)),
            'exp' => time() + 60,
            'kid' => config('box.public_key_id'),
        ];
    }

    /**
     * @throws Exception
     */
    protected function generateKey(): OpenSSLAsymmetricKey|bool
    {
        $password = config('box.passphrase');

        if (! File::exists(config('box.private_key')) || empty($password)) {
            throw new Exception('Could not find private-key.pem file in the base directory.');
        }

        $privateKey = File::get(config('box.private_key'));

        return openssl_pkey_get_private($privateKey, $password);
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
