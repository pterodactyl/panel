<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

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

    public function handle(User $user): PublicKeyCredentialCreationOptions
    {
        $entity = new PublicKeyCredentialUserEntity($user->username, $user->uuid, $user->email, null);

        $excluded = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialDescriptor();
        })->values()->toArray();

        $server = $this->webauthnServerRepository->getServer($user);

        return $server->generatePublicKeyCredentialCreationOptions(
            $entity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excluded
        );
    }
}
