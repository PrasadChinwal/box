<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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

    /**
     * @param  string  $toUser
     * @param  bool  $notify
     * @return Collection
     * @throws RequestException
     */
    public function transfer(string $toUser, bool $notify = false): Collection
    {
        return Http::asJson()
            ->withToken($this->getAccessToken())
            ->withQueryParameters([
                'notify' => $notify
            ])
            ->put(`{$this->endpoint}{$this->id}/folders/0`, [
                'owned_by' => [
                    'id' => $toUser
                ]
            ])
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @param  bool  $force
     * @param  bool  $notify
     * @return Response
     * @throws RequestException
     */
    public function delete(bool $force = false, bool $notify = true): Response
    {
        Http::withToken($this->getAccessToken())
            ->withQueryParameters([
                'force' => $force,
                'notify' => $notify
            ])
            ->delete($this->endpoint . $this->id)
            ->throwUnlessStatus(204);
        return new Response('User has been deleted successfully');
    }
}
