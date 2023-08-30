<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class BoxUser extends Box
{
    protected string $endpoint = 'https://api.box.com/2.0/users/';

    /**
     * @var string User id
     */
    private string $id;

    /**
     * @return $this
     */
    public function whereId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @see https://developer.box.com/reference/get-users-me/
     *
     * @throws RequestException
     */
    public function get(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.'me')
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see
     *
     * @throws RequestException
     */
    public function memberships(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id.'/memberships')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
