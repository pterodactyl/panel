<?php

namespace Pterodactyl\Repositories\SecurityKeys;

use Webauthn\Server;
use Pterodactyl\Models\User;
use Webauthn\PublicKeyCredentialRpEntity;

final class WebauthnServerRepository
{
    private PublicKeyCredentialRpEntity $rpEntity;

    public function __construct()
    {
        $url = str_replace(['http://', 'https://'], '', config('app.url'));

        $this->rpEntity = new PublicKeyCredentialRpEntity(config('app.name'), trim($url, '/'));
    }

    public function getServer(User $user)
    {
        return new Server(
            $this->rpEntity,
            PublicKeyCredentialSourceRepository::factory($user)
        );
    }
}
