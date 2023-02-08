<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

use Pterodactyl\Models\User;
use Webauthn\PublicKeyCredentialCreationOptions;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;

class CreatePublicKeyCredentialService
{
    protected WebauthnServerRepository $webauthnServerRepository;

    public function __construct(WebauthnServerRepository $webauthnServerRepository)
    {
        $this->webauthnServerRepository = $webauthnServerRepository;
    }

    /**
     * @throws \Webauthn\Exception\InvalidDataException
     */
    public function handle(User $user): PublicKeyCredentialCreationOptions
    {
        return $this->webauthnServerRepository->getPublicKeyCredentialCreationOptions($user);
    }
}
