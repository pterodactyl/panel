<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;

class CreatePublicKeyCredentialsService
{
    protected WebauthnServerRepository $webauthnServerRepository;

    public function __construct(WebauthnServerRepository $webauthnServerRepository)
    {
        $this->webauthnServerRepository = $webauthnServerRepository;
    }

    public function handle(User $user, ?string $displayName): PublicKeyCredentialCreationOptions
    {
        $id = Uuid::uuid4()->toString();

        $entity = new PublicKeyCredentialUserEntity($user->uuid, $id, $name ?? $user->email);

        $excluded = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->toCredentialsDescriptor();
        })->values()->toArray();

        $server = $this->webauthnServerRepository->getServer($user);

        return $server->generatePublicKeyCredentialCreationOptions(
            $entity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excluded
        );
    }
}
