<?php

namespace Pterodactyl\Services\Users\SecurityKeys;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\SecurityKey;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;

class StoreSecurityKeyService
{
    protected WebauthnServerRepository $serverRepository;

    protected ?ServerRequestInterface $request = null;

    protected ?string $keyName = null;

    public function __construct(WebauthnServerRepository $serverRepository)
    {
        $this->serverRepository = $serverRepository;
    }

    /**
     * Sets the server request interface on the service, this is needed by the attestation
     * checking service on the Webauthn server.
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Sets the security key's name. If not provided a random string will be used.
     */
    public function setKeyName(?string $name): self
    {
        $this->keyName = $name;

        return $this;
    }

    /**
     * Validates and stores a new hardware security key on a user's account.
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $registration, PublicKeyCredentialCreationOptions $options): SecurityKey
    {
        Assert::notNull($this->request, 'A request interface must be set on the service before it can be called.');

        $source = $this->serverRepository->getServer($user)
            ->loadAndCheckAttestationResponse(json_encode($registration), $options, $this->request);

        // Unfortunately this repository interface doesn't define a response — it is explicitly
        // void — so we need to just query the database immediately after this to pull the information
        // we just stored to return to the caller.
        /** @var \Pterodactyl\Models\SecurityKey $key */
        $key = $user->securityKeys()->forceCreate([
            'uuid' => Uuid::uuid4(),
            'name' => $this->keyName ?? 'Security Key (' . Str::random() . ')',
            'public_key_id' => $source->getPublicKeyCredentialId(),
            'public_key' => $source->getCredentialPublicKey(),
            'aaguid' => $source->getAaguid(),
            'type' => $source->getType(),
            'transports' => $source->getTransports(),
            'attestation_type' => $source->getAttestationType(),
            'trust_path' => $source->getTrustPath(),
            'user_handle' => $user->uuid,
            'counter' => $source->getCounter(),
            'other_ui' => $source->getOtherUI(),
        ]);

        return $key;
    }
}
