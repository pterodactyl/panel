<?php

namespace Pterodactyl\Repositories\SecurityKeys;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Illuminate\Container\Container;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Pterodactyl\Models\SecurityKey;
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
        /** @var \Pterodactyl\Models\SecurityKey $key */
        $key = $this->user->securityKeys()
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
        $results = $this->user->securityKeys()
            ->where('user_handle', $entity->getId())
            ->get();

        return $results->map(function (SecurityKey $key) {
            return $key->toCredentialSource();
        })->values()->toArray();
    }

    /**
     * Save a credential to the database and link it with the user.
     *
     * @throws \Throwable
     */
    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        $this->user->securityKeys()->forceCreate([
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => $this->user->id,
            'public_key_id' => base64_encode($source->getPublicKeyCredentialId()),
            'public_key' => base64_encode($source->getCredentialPublicKey()),
            'aaguid' => $source->getAaguid()->toString(),
            'type' => $source->getType(),
            'transports' => $source->getTransports(),
            'attestation_type' => $source->getAttestationType(),
            'trust_path' => $source->getTrustPath()->jsonSerialize(),
            'user_handle' => $source->getUserHandle(),
            'counter' => $source->getCounter(),
            'other_ui' => $source->getOtherUI(),
        ]);
    }

    /**
     * Returns a new instance of the repository with the provided user attached.
     */
    public static function factory(User $user): self
    {
        return Container::getInstance()->make(static::class, ['user' => $user]);
    }
}
