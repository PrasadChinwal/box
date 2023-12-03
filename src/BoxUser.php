<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use PrasadChinwal\Box\Dto\User;

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
    public function all(): \Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint)
            ->throwUnlessStatus(200)
            ->collect('entries');
        return User::collection($response);
    }

    /**
     * @see https://developer.box.com/reference/get-users-me/
     *
     * @throws RequestException
     */
    public function get(): User
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.'me')
            ->throwUnlessStatus(200)
            ->collect();
        return User::from($response);
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
     * @see https://developer.box.com/reference/get-users-me/
     *
     * @throws RequestException
     */
    public function first(): User
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
        return User::from($response);
    }

    /**
     * @see https://developer.box.com/reference/delete-users-id/
     *
     * @throws RequestException
     */
    public function delete(bool $force=false, bool $notify = false): Response
    {
        $response = Http::withToken($this->getAccessToken())
            ->delete($this->endpoint.$this->id, [
                'force' => $force,
                'notify' => $notify
            ])
            ->throwUnlessStatus(204);
        if($response->status() === 204) {
            return new Response('Successfully deleted the User!');
        }
        return new Response('Could not delete the user!');
    }

    /**
     * Transfers the contents of root folder of user to another user.
     * @param string $to
     * @return bool
     * @throws RequestException
     */
    public function transfer(string $to): bool
    {
        $response = Http::withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$this->id."/folders/0", [
                'owned_by' => [
                    'id' => $to
                ]
            ])->throwUnlessStatus(200);
        if($response->status() === 200) {
            return true;
        }
        return false;
    }

    /**
     * @throws RequestException
     */
    public function findByEmail(string $email)
    {
        $result = Http::withToken($this->getAccessToken())
            ->get($this->endpoint, [
                'filter_term' => $email,
                'limit' => 10,
                'user_type' => 'all'
            ])
            ->throwUnlessStatus(200)
        ->collect('entries');
        return User::collection($result);
    }
}
