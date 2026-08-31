<?php

namespace PrasadChinwal\Box;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use PrasadChinwal\Box\Dto\User;
use PrasadChinwal\Box\Exceptions\OperationException;

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
    public function all(): Collection
    {
        $response = $this->boxRequest()
            ->get($this->endpoint)
            ->throwUnlessStatus(200)
            ->collect('entries');

        return User::collect($response, Collection::class);
    }

    /**
     * @see https://developer.box.com/reference/get-users-me/
     *
     * @throws RequestException
     */
    public function get(): User
    {
        $response = $this->boxRequest()
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
        return $this->boxRequest()
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
        $response = $this->boxRequest()
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
        $this->boxRequest()
            ->delete($this->endpoint.$this->id, [
                'force' => $force,
                'notify' => $notify
            ])
            ->throwUnlessStatus(204);

        return new Response('Successfully deleted the User!');
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
        return $this->jsonRequest()
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
    public function findByEmail(string $email): Collection
    {
        $response = $this->boxRequest()
            ->get($this->endpoint, [
                'filter_term' => $email,
                'limit' => 10,
                'user_type' => 'all'
            ])
            ->throwUnlessStatus(200)
            ->collect('entries');

        return User::collect($response, Collection::class);
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
            throw OperationException::fromThrowable('Could not deprovision the Box user.', $exception);
        }
    }
}
