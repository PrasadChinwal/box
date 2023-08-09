<?php

namespace PrasadChinwal\Box\Traits;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PrasadChinwal\Box\Box;

trait CanShare
{

    /**
     * @throws Exception
     */
    public static function whereLink($sharedLink, string $sharedLinkPassword = ''): static
    {
        $class = new self(new Box());
        $class->sharedLink = $sharedLink;
        $class->sharedLinkPassword = $sharedLinkPassword;
        return $class;
    }

    /**
     * @throws RequestException
     */
    public function find(): \Illuminate\Support\Collection
    {
        //ddd($this->box->getAccessToken());
        if(!$this->sharedLink) {
            throw new ValidationException('Please provide shared link for file/folder!');
        }
        return Http::withToken($this->box->getAccessToken())
            ->get($this->sharedLinkUrl, [
                'shared_link' => $this->sharedLink,
                'shared_link_password' => $this->sharedLinkPassword
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @throws RequestException
     */
    public function getSharedLink(): \Illuminate\Support\Collection
    {
        if(!$this->id) {
            throw new ValidationException('Please provide fileId of file/folder!');
        }
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }
}
