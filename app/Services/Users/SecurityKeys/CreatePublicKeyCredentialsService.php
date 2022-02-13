<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
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
        $excluded = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialDescriptor();
        })->values()->toArray();

        $server = $this->webauthnServerRepository->getServer($user);

        return $server->generatePublicKeyCredentialCreationOptions(
            $user->toPublicKeyCredentialEntity(),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excluded
        );
    }
}
