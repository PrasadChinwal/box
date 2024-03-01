<?php

namespace PrasadChinwal\Box;

use Exception;
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
    public function all()
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint)
            ->throwUnlessStatus(200)
            ->collect('entries');
        return json_encode($response);
        return User::collection($response);
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
    public function first()
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->endpoint.$this->id)
            ->throwUnlessStatus(200)
            ->collect();
        return json_encode($response);
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
     *
     * @param string $from User id of the account to transfer from
     * @param string $to   User id of the account to transfer to
     *
     * @throws RequestException
     */
    public function transfer(string $from, string $to)
    {
        return Http::withToken($this->getAccessToken())
            ->asJson()
            ->put($this->endpoint.$from."/folders/0", [
                'owned_by' => [
                    'id' => $to
                ]
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
                'user_type' => 'all'
            ])
            ->throwUnlessStatus(200)
            ->collect('entries');
//        return User::collection($result);
    }

    /**
     * Decommission a give user by transferring the data to root user.
     * @param string $transferTo user id of the person to transfer the data to.
     *
     */
    public function deprovision(string $transferFrom, string $transferTo)
    {
        try {
            return $this->transfer($transferFrom, $transferTo);
        } catch (Exception $exception) {
            dump("Error during transferring user data!");
            dd($exception);
        }

//        try {
//            $delete = $this->delete();
//        } catch (Exception $exception) {
//            dump("Error during Deleting a user");
//            dd($exception);
//        }
    }
}
