<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class BoxUser
{
    protected string $endpoint = 'https://api.box.com/2.0/users/';

    private Box $box;

    private string $id;

    /**
     *
     * @throws \Exception
     */
    public function __construct(Box $box)
    {
        $this->box = $box;
    }

    public function __call(string $name, array $arguments)
    {
        return (new BoxUser(new Box()))->{$name}(...$arguments);
    }

    /**
     * @throws RequestException
     */
    public function get(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . 'me')
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @param string $id
     * @return $this
     */
    public function whereId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @throws RequestException
     */
    public function memberships(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id . '/memberships')
            ->throwUnlessStatus(200)
            ->collect();
    }
}
