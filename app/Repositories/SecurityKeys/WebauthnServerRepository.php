<?php

namespace Pterodactyl\Repositories\SecurityKeys;

use Cose\Algorithms;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialParameters;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AuthenticatorAttestationResponse;
use Cose\Algorithm\Manager as AlgorithmManager;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

final class WebauthnServerRepository
{
    private PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository;

    private PublicKeyCredentialRpEntity $rpEntity;
    private PublicKeyCredentialLoader $credentialLoader;
    private AuthenticatorAssertionResponseValidator $assertionValidator;
    private AuthenticatorAttestationResponseValidator $attestationValidator;

    public function __construct(PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository)
    {
        $url = str_replace(['http://', 'https://'], '', config('app.url'));

        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;

        $this->rpEntity = new PublicKeyCredentialRpEntity(config('app.name'), trim($url, '/'));
        $this->credentialLoader = new PublicKeyCredentialLoader(new AttestationObjectLoader(new AttestationStatementSupportManager()));
        $this->assertionValidator = new AuthenticatorAssertionResponseValidator(
            $this->publicKeyCredentialSourceRepository,
            null,
            ExtensionOutputCheckerHandler::create(),
            AlgorithmManager::create(),
        );
        $this->attestationValidator = new AuthenticatorAttestationResponseValidator(
            new AttestationStatementSupportManager(),
            $this->publicKeyCredentialSourceRepository,
            null,
            new ExtensionOutputCheckerHandler(),
        );
    }

    /**
     * @throws \Webauthn\Exception\InvalidDataException
     */
    public function getPublicKeyCredentialCreationOptions(User $user): PublicKeyCredentialCreationOptions
    {
        $excluded = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialDescriptor();
        })->values()->toArray();

        $challenge = Str::random(16);

        return (new PublicKeyCredentialCreationOptions(
            $this->rpEntity,
            $user->toPublicKeyCredentialEntity(),
            $challenge,
            [
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES384),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES512),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS384),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS512),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS384),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS512),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED512),
            ],
        ))
            ->setTimeout(30_000)
            ->excludeCredentials(...$excluded)
            ->setAttestation(PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE)
            ->setAuthenticatorSelection(AuthenticatorSelectionCriteria::create());
    }

    /**
     * @throws \Webauthn\Exception\InvalidDataException
     */
    public function generatePublicKeyCredentialRequestOptions(User $user): PublicKeyCredentialRequestOptions
    {
        $allowedCredentials = $user->securityKeys->map(function (SecurityKey $key) {
            return $key->getPublicKeyCredentialDescriptor();
        })->values()->toArray();

        return (new PublicKeyCredentialRequestOptions(Str::random(32)))
            ->allowCredentials(...$allowedCredentials)
            ->setUserVerification(PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED);
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function loadAndCheckAssertionResponse(
        User $user,
        array $data,
        PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        ServerRequestInterface $request
    ): PublicKeyCredentialSource {
        $credential = $this->credentialLoader->loadArray($data);

        $authenticatorAssertionResponse = $credential->getResponse();
        if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            // TODO
            throw new \Exception('');
        }

        return $this->assertionValidator->check(
            $credential->getRawId(),
            $authenticatorAssertionResponse,
            $publicKeyCredentialRequestOptions,
            $request,
            null, // TODO: use handle?
//            $user->toPublicKeyCredentialEntity()
        );
    }

    /**
     * Register a new security key for a user.
     *
     * @throws \Throwable
     * @throws \JsonException
     */
    public function loadAndCheckAttestationResponse(
        User $user,
        array $data,
        PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
        ServerRequestInterface $request
    ): PublicKeyCredentialSource {
        $credential = $this->credentialLoader->loadArray($data);

        $authenticatorAttestationResponse = $credential->getResponse();
        if (!$authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            // TODO
            throw new \Exception('');
        }

        return $this->attestationValidator->check(
            $authenticatorAttestationResponse,
            $publicKeyCredentialCreationOptions,
            $request,
        );
    }
}
