<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Webauthn\PublicKeyCredentialRequestOptions;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;

class GeneratePublicKeyCredentialsRequestService
{
    protected WebauthnServerRepository $serverRepository;

    /**
     * @param \Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository $serverRepository
     */
    public function __construct(WebauthnServerRepository $serverRepository)
    {
        $this->serverRepository = $serverRepository;
    }

    /**
     * @param \Pterodactyl\Models\User $user
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public function handle(User $user): PublicKeyCredentialRequestOptions
    {
        $credentials = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialDescriptor();
        })->values()->toArray();

        $response = $this->serverRepository->getServer($user)
            ->generatePublicKeyCredentialRequestOptions(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, $credentials
            );

        return $response->setTimeout(300);
    }
}
