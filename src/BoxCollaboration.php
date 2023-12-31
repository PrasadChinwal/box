<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class BoxCollaboration extends Box
{
    protected string $endpoint = 'https://api.box.com/2.0/collaborations/';

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
     * @see https://developer.box.com/reference/get-collaborations-id/
     *
     * @throws RequestException
     */
    public function get(): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/post-collaborations/
     *
     * @throws RequestException
     */
    public function create(array $attributes): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->post($this->endpoint, $attributes)
            ->throwUnlessStatus(201)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/put-collaborations-id/
     *
     * @throws RequestException
     */
    public function update(array $attributes): \Illuminate\Support\Collection
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @see https://developer.box.com/reference/delete-collaborations-id/
     *
     * @throws RequestException
     */
    public function delete(): Response
    {
        Http::withToken($this->getAccessToken())
            ->asJson()
            ->delete($this->endpoint.$this->id)
            ->throwUnlessStatus(204);

        return new Response("Successfully deleted collaboration {$this->id}");
    }
}
