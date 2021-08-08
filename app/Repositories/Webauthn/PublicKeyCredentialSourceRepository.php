<?php

namespace Pterodactyl\Repositories\Webauthn;

use Pterodactyl\Models\User;
use Illuminate\Container\Container;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Pterodactyl\Models\HardwareSecurityKey;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyRepositoryInterface;

class PublicKeyCredentialSourceRepository implements PublicKeyRepositoryInterface
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Find a single hardware security token for a user by uzing the credential ID.
     */
    public function findOneByCredentialId(string $id): ?PublicKeyCredentialSource
    {
        /** @var \Pterodactyl\Models\HardwareSecurityKey $key */
        $key = $this->user->hardwareSecurityKeys()
            ->where('public_key_id', $id)
            ->first();

        return $key ? $key->toCredentialSource() : null;
    }

    /**
     * Find all of the hardware tokens that exist for the user using the given
     * entity handle.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $entity): array
    {
        $results = $this->user->hardwareSecurityKeys()
            ->where('user_handle', $entity->getId())
            ->get();

        return $results->map(function (HardwareSecurityKey $key) {
            return $key->toCredentialSource();
        })->values()->toArray();
    }

    /**
     * Save a credential to the database and link it with the user.
     */
    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        // todo: implement
    }

    /**
     * Returns a new instance of the repository with the provided user attached.
     */
    public static function factory(User $user): self
    {
        return Container::getInstance()->make(static::class, ['user' => $user]);
    }
}
