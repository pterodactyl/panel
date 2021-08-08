<?php

namespace Pterodactyl\Services\Users\HardwareSecurityKeys;

use Webauthn\Server;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Webauthn\PublicKeyCredentialRpEntity;
use Pterodactyl\Models\HardwareSecurityKey;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Pterodactyl\Repositories\Webauthn\PublicKeyCredentialSourceRepository;

class CreatePublicKeyCredentialsService
{
    protected PublicKeyCredentialRpEntity $rpEntity;

    public function __construct()
    {
        $url = str_replace(['http://', 'https://'], '', config('app.url'));

        $this->rpEntity = new PublicKeyCredentialRpEntity(config('app.name'), trim($url, '/'));
    }

    public function handle(User $user): PublicKeyCredentialCreationOptions
    {
        $id = Uuid::uuid4()->toString();

        $entity = new PublicKeyCredentialUserEntity($user->uuid, $id, $user->email);

        $excluded = $user->hardwareSecurityKeys->map(function (HardwareSecurityKey $key) {
            return $key->toCredentialsDescriptor();
        })->values()->toArray();

        return $this->getServerInstance($user)->generatePublicKeyCredentialCreationOptions(
            $entity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excluded
        );
    }

    protected function getServerInstance(User $user)
    {
        return new Server(
            $this->rpEntity,
            PublicKeyCredentialSourceRepository::factory($user)
        );
    }
}
