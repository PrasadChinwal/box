<?php

namespace PrasadChinwal\Box;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

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
    public function all()
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint)
            ->throwUnlessStatus(200)
            ->collect('entries');

        return json_encode($response);
    }

    /**
     * @see https://developer.box.com/reference/get-users-me/
     *
     * @throws RequestException
     */
    public function get()
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.'me')
            ->throwUnlessStatus(200)
            ->collect();

        return json_encode($response);
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
    public function first()
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();

        return json_encode($response);
    }

    /**
     * @see https://developer.box.com/reference/delete-users-id/
     *
     * @throws RequestException
     */
    public function delete(bool $force = false, bool $notify = false): Response
    {
        $response = Http::withToken($this->getAccessToken())
            ->delete($this->endpoint.$this->id, [
                'force' => $force,
                'notify' => $notify,
            ])
            ->throwUnlessStatus(204);
        if ($response->status() === 204) {
            return new Response('Successfully deleted the User!');
        }

        return new Response('Could not delete the user!');
    }

    /**
     * Transfers the contents of root folder of user to another user.
     *
     * @param  string  $from  User id of the account to transfer from
     * @param  string  $to  User id of the account to transfer to
     *
     * @throws RequestException
     */
    public function transfer(string $from, string $to)
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$from.'/folders/0', [
                'owned_by' => [
                    'id' => $to,
                ],
            ])->throwUnlessStatus(200)
            ->json();
    }

    /**
     * @throws RequestException
     */
    public function findByEmail(string $email)
    {
        return Http::withToken($this->getAccessToken())
            ->get($this->endpoint, [
                'filter_term' => $email,
                'limit' => 10,
                'user_type' => 'all',
            ])
            ->throwUnlessStatus(200)
            ->collect('entries');
    }

    /**
     * Creates a new box user.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function create(array $config)
    {
        if (! Arr::has($config, ['name', 'login'])) {
            throw new \Exception('Please provide a name and login for the user!');
        }
        try {
            Http::withToken($this->getAccessToken())
                ->post($this->endpoint, $config)
                ->throwUnlessStatus(201);
        } catch (\Exception $exception) {
            throw new \Exception('Could not create box user!');
        }
    }
}
