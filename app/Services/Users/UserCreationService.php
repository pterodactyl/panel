<?php

namespace Pterodactyl\Services\Users;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class UserCreationService
{
    private SettingsRepositoryInterface $settings;
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Illuminate\Contracts\Auth\PasswordBroker
     */
    private $passwordBroker;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * CreationService constructor.
     */
    public function __construct(ConnectionInterface $connection, Hasher $hasher, PasswordBroker $passwordBroker, UserRepositoryInterface $repository, SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->connection = $connection;
        $this->hasher = $hasher;
        $this->passwordBroker = $passwordBroker;
        $this->repository = $repository;
    }

    /**
     * Create a new user on the system.
     *
     * @return \Pterodactyl\Models\User
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $this->connection->beginTransaction();
        if (!isset($data['password']) || empty($data['password'])) {
            $generateResetToken = true;
            $data['password'] = $this->hasher->make(str_random(30));
        }

        /** @var \Pterodactyl\Models\User $user */
        $user = $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]), true, true);

        if (isset($generateResetToken)) {
            $token = $this->passwordBroker->createToken($user);
        }

        if ($this->settings->get('jexactyl::approvals:enabled') === 'true' && $this->settings->get('jexactyl::approvals:webhook')) {
            $name = $this->settings->get('settings::app:name', 'Jexactyl');
            $icon = $this->settings->get('settings::app:logo', 'https://avatars.githubusercontent.com/u/91636558');
            $webhook_data = [
                'username' => $name,
                'avatar_url' => $icon,
                'embeds' => [
                    [
                        'title' => $name . ' - Registration Request',
                        'color' => 2718223,
                        'description' => 'A new account has been created.',
                        'fields' => [
                            [
                                'name' => 'Username:',
                                'value' => $data['username'],
                            ],
                            [
                                'name' => 'Email:',
                                'value' => $data['email'],
                            ],
                            [
                                'name' => 'Approve:',
                                'value' => env('APP_URL') . '/admin/approvals',
                            ],
                        ],
                        'footer' => ['text' => $name, 'icon_url' => $icon],
                        'timestamp' => date('c'),
                    ],
                ],
            ];

            try {
                Http::withBody(json_encode($webhook_data), 'application/json')->post($this->settings->get('jexactyl::approvals:webhook'));
            } catch (\Exception $e) {
            }
        }

        $this->connection->commit();
        $user->notify(new AccountCreated($user, $token ?? null));

        return $user;
    }
}
