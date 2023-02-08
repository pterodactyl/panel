<?php

namespace Pterodactyl\Repositories\SecurityKeys;

use Pterodactyl\Models\User;
use Illuminate\Container\Container;
use Pterodactyl\Models\SecurityKey;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyRepositoryInterface;

class PublicKeyCredentialSourceRepository implements PublicKeyRepositoryInterface
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Find a single hardware security token for a user by using the credential ID.
     */
    public function findOneByCredentialId(string $id): ?PublicKeyCredentialSource
    {
        /** @var \Pterodactyl\Models\SecurityKey $key */
        $key = $this->user->securityKeys()
            ->where('public_key_id', base64_encode($id))
            ->first();

        return optional($key)->getPublicKeyCredentialSource();
    }

    /**
     * Find all the hardware tokens that exist for the user using the given
     * entity handle.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $entity): array
    {
        $results = $this->user->securityKeys()
            ->where('user_handle', $entity->getId())
            ->get();

        return $results->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialSource();
        })->values()->toArray();
    }

    /**
     * Save a credential to the database and link it with the user.
     *
     * @throws \Throwable
     */
    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        // no-op — we handle creation of the keys in StoreSecurityKeyService
        //
        // If you put logic in here it is triggered on each login.
    }

    /**
     * Returns a new instance of the repository with the provided user attached.
     */
    public static function factory(User $user): self
    {
        return Container::getInstance()->make(static::class, ['user' => $user]);
    }
}
