<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use PhpMyAdmin\TwoFactor;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server as WebauthnServer;
use Webauthn\TrustPath\EmptyTrustPath;
use Webmozart\Assert\Assert;

use function array_map;
use function base64_encode;
use function json_decode;
use function sodium_base642bin;
use function sodium_bin2base64;

use const SODIUM_BASE64_VARIANT_ORIGINAL;
use const SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

final class WebauthnLibServer implements Server
{
    /** @var TwoFactor */
    private $twofactor;

    public function __construct(TwoFactor $twofactor)
    {
        $this->twofactor = $twofactor;
    }

    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array
    {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $server = new WebauthnServer($relyingPartyEntity, $publicKeyCredentialSourceRepository);
        $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            [],
            AuthenticatorSelectionCriteria::createFromArray([
                'authenticatorAttachment' => 'cross-platform',
                'userVerification' => 'discouraged',
            ])
        );
        /** @psalm-var array{
         *   challenge: non-empty-string,
         *   rp: array{name: non-empty-string, id: non-empty-string},
         *   user: array{id: non-empty-string, name: non-empty-string, displayName: non-empty-string},
         *   pubKeyCredParams: list<array{alg: int, type: 'public-key'}>,
         *   authenticatorSelection: array<string, string>,
         *   timeout: positive-int,
         *   attestation: non-empty-string
         * } $creationOptions */
        $creationOptions = $publicKeyCredentialCreationOptions->jsonSerialize();
        $creationOptions['challenge'] = sodium_bin2base64(
            sodium_base642bin($creationOptions['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
        Assert::stringNotEmpty($creationOptions['challenge']);

        return $creationOptions;
    }

    public function getCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        array $allowedCredentials
    ): array {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $server = new WebauthnServer($relyingPartyEntity, $publicKeyCredentialSourceRepository);
        $credentialSources = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $credentialSources
        );
        $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
            'discouraged',
            $allowedCredentials
        );
        /**
         * @psalm-var array{
         *   challenge: string,
         *   allowCredentials?: list<array{id: non-empty-string, type: non-empty-string}>
         * } $requestOptions
         */
        $requestOptions = $publicKeyCredentialRequestOptions->jsonSerialize();
        $requestOptions['challenge'] = sodium_bin2base64(
            sodium_base642bin($requestOptions['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
        if (isset($requestOptions['allowCredentials'])) {
            foreach ($requestOptions['allowCredentials'] as $key => $credential) {
                $requestOptions['allowCredentials'][$key]['id'] = sodium_bin2base64(
                    sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                    SODIUM_BASE64_VARIANT_ORIGINAL
                );
            }
        }

        return $requestOptions;
    }

    public function parseAndValidateAssertionResponse(
        string $assertionResponseJson,
        array $allowedCredentials,
        string $challenge,
        ServerRequestInterface $request
    ): void {
        Assert::string($this->twofactor->config['settings']['userHandle']);
        $userHandle = sodium_base642bin(
            $this->twofactor->config['settings']['userHandle'],
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
        $userEntity = new PublicKeyCredentialUserEntity(
            $this->twofactor->user,
            $userHandle,
            $this->twofactor->user
        );
        $host = $request->getUri()->getHost();
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $host . ')', $host);
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $server = new WebauthnServer($relyingPartyEntity, $publicKeyCredentialSourceRepository);
        $requestOptions = PublicKeyCredentialRequestOptions::createFromArray([
            'challenge' => $challenge,
            'allowCredentials' => $allowedCredentials,
            'rpId' => $host,
            'timeout' => 60000,
        ]);
        Assert::isInstanceOf($requestOptions, PublicKeyCredentialRequestOptions::class);
        $server->loadAndCheckAssertionResponse(
            $assertionResponseJson,
            $requestOptions,
            $userEntity,
            $request
        );
    }

    public function parseAndValidateAttestationResponse(
        string $attestationResponse,
        string $credentialCreationOptions,
        ServerRequestInterface $request
    ): array {
        $creationOptions = json_decode($credentialCreationOptions, true);
        Assert::isArray($creationOptions);
        Assert::keyExists($creationOptions, 'challenge');
        Assert::keyExists($creationOptions, 'user');
        Assert::isArray($creationOptions['user']);
        Assert::keyExists($creationOptions['user'], 'id');
        $host = $request->getUri()->getHost();
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $host . ')', $host);
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $server = new WebauthnServer($relyingPartyEntity, $publicKeyCredentialSourceRepository);
        $creationOptionsArray = [
            'rp' => ['name' => 'phpMyAdmin (' . $host . ')', 'id' => $host],
            'pubKeyCredParams' => [
                ['alg' => -257, 'type' => 'public-key'], // RS256
                ['alg' => -259, 'type' => 'public-key'], // RS512
                ['alg' => -37, 'type' => 'public-key'], // PS256
                ['alg' => -39, 'type' => 'public-key'], // PS512
                ['alg' => -7, 'type' => 'public-key'], // ES256
                ['alg' => -36, 'type' => 'public-key'], // ES512
                ['alg' => -8, 'type' => 'public-key'], // EdDSA
            ],
            'challenge' => $creationOptions['challenge'],
            'attestation' => 'none',
            'user' => [
                'name' => $this->twofactor->user,
                'id' => $creationOptions['user']['id'],
                'displayName' => $this->twofactor->user,
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'cross-platform',
                'userVerification' => 'discouraged',
            ],
            'timeout' => 60000,
        ];
        $credentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($creationOptionsArray);
        Assert::isInstanceOf($credentialCreationOptions, PublicKeyCredentialCreationOptions::class);
        $publicKeyCredentialSource = $server->loadAndCheckAttestationResponse(
            $attestationResponse,
            $credentialCreationOptions,
            $request
        );

        return $publicKeyCredentialSource->jsonSerialize();
    }

    private function createPublicKeyCredentialSourceRepository(): PublicKeyCredentialSourceRepository
    {
        return new class ($this->twofactor) implements PublicKeyCredentialSourceRepository {
            /** @var TwoFactor */
            private $twoFactor;

            public function __construct(TwoFactor $twoFactor)
            {
                $this->twoFactor = $twoFactor;
            }

            public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
            {
                $data = $this->read();
                if (isset($data[base64_encode($publicKeyCredentialId)])) {
                    return PublicKeyCredentialSource::createFromArray($data[base64_encode($publicKeyCredentialId)]);
                }

                return null;
            }

            /**
             * @return PublicKeyCredentialSource[]
             */
            public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
            {
                $sources = [];
                foreach ($this->read() as $data) {
                    $source = PublicKeyCredentialSource::createFromArray($data);
                    if ($source->getUserHandle() !== $publicKeyCredentialUserEntity->getId()) {
                        continue;
                    }

                    $sources[] = $source;
                }

                return $sources;
            }

            public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
            {
                $data = $this->read();
                $id = $publicKeyCredentialSource->getPublicKeyCredentialId();
                $data[base64_encode($id)] = $publicKeyCredentialSource->jsonSerialize();
                $this->write($data);
            }

            /**
             * @return mixed[][]
             */
            private function read(): array
            {
                /** @psalm-var list<mixed[]> $credentials */
                $credentials = $this->twoFactor->config['settings']['credentials'];
                foreach ($credentials as &$credential) {
                    if (isset($credential['trustPath'])) {
                        continue;
                    }

                    $credential['trustPath'] = ['type' => EmptyTrustPath::class];
                }

                return $credentials;
            }

            /**
             * @param mixed[] $data
             */
            private function write(array $data): void
            {
                $this->twoFactor->config['settings']['credentials'] = $data;
            }
        };
    }
}
