<?php

namespace Pterodactyl\Services\Users;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Notifications\VerifyEmail;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class UserCreationService
{
    /**
     * UserCreationService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private Hasher $hasher,
        private PasswordBroker $passwordBroker,
        private UserRepositoryInterface $repository,
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * Create a new user on the system.
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): User
    {
        $name = $this->settings->get('settings::app:name', 'Jexactyl');

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

        if (!$data['verified']) {
            $token = $this->genStr();
            DB::table('verification_tokens')->insert(['user' => $user->id, 'token' => $token]);
            $user->notify(new VerifyEmail($user, $name, $token));
        }

        $this->connection->commit();

        try {
            $user->notify(new AccountCreated($user, $token ?? null));
        } catch (\Exception $e) {
            // If the email system isn't active, still let users create accounts.
        }

        return $user;
    }

    private function genStr(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $pieces = [];
        $max = mb_strlen($chars, '8bit') - 1;
        for ($i = 0; $i < 32; ++$i) {
            $pieces[] = $chars[mt_rand(0, $max)];
        }

        return implode('', $pieces);
    }
}
