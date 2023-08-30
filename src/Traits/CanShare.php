<?php

namespace PrasadChinwal\Box\Traits;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PrasadChinwal\Box\Box;

trait CanShare
{
    /**
     * @throws Exception
     */
    public function whereLink($sharedLink, string $sharedLinkPassword = ''): static
    {
        $this->sharedLink = $sharedLink;
        $this->sharedLinkPassword = $sharedLinkPassword;

        return $this;
    }

    /**
     * @see https://developer.box.com/reference/get-shared-items/
     * @see https://developer.box.com/reference/get-shared-items--folders/
     *
     * @throws RequestException
     */
    public function find(): Collection
    {
        if (! $this->sharedLink) {
            throw new ValidationException('Please provide shared link for file/folder!');
        }

        return Http::withToken($this->getAccessToken())
            ->get($this->sharedLinkUrl, [
                'shared_link' => $this->sharedLink,
                'shared_link_password' => $this->sharedLinkPassword,
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/get-shared-items--folders/
     * @see https://developer.box.com/reference/get-folders-id--get-shared-link/
     *
     * @throws RequestException
     */
    public function getSharedLink(): Collection
    {
        if (! $this->id) {
            throw new ValidationException('Please provide fileId of file/folder!');
        }

        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-folders-id--add-shared-link/
     * @see https://developer.box.com/reference/put-files-id--add-shared-link/
     *
     * @throws Exception
     */
    public function createSharedLink(array $attributes): Collection
    {
        return Http::asForm()
            ->withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-folders-id--remove-shared-link/
     * @see https://developer.box.com/reference/put-files-id--remove-shared-link/
     *
     * @throws RequestException
     */
    public function removeSharedLink(): Collection
    {
        $attributes = [
            'attributes' => json_encode(['shared_link' => null]),
        ];

        return Http::asForm()
            ->withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }
}
