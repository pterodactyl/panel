<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Psr\Http\Message\ServerRequestInterface;
use SodiumException;
use Throwable;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function hash;
use function hash_equals;
use function json_decode;
use function mb_strlen;
use function mb_substr;
use function ord;
use function parse_url;
use function random_bytes;
use function sodium_base642bin;
use function sodium_bin2base64;
use function unpack;

use const PHP_URL_HOST;
use const SODIUM_BASE64_VARIANT_ORIGINAL;
use const SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

/**
 * Web Authentication API server.
 *
 * @see https://www.w3.org/TR/webauthn-3/
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API
 * @see https://webauthn.guide/
 */
final class CustomServer implements Server
{
    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array
    {
        return [
            'challenge' => $this->generateChallenge(),
            'rp' => ['name' => 'phpMyAdmin (' . $relyingPartyId . ')', 'id' => $relyingPartyId],
            'user' => ['id' => $userId, 'name' => $userName, 'displayName' => $userName],
            'pubKeyCredParams' => $this->getCredentialParameters(),
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'cross-platform',
                'userVerification' => 'discouraged',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
    }

    public function getCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        array $allowedCredentials
    ): array {
        foreach ($allowedCredentials as $key => $credential) {
            $allowedCredentials[$key]['id'] = sodium_bin2base64(
                sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                SODIUM_BASE64_VARIANT_ORIGINAL
            );
        }

        return [
            'challenge' => $this->generateChallenge(),
            'allowCredentials' => $allowedCredentials,
            'timeout' => 60000,
            'attestation' => 'none',
            'userVerification' => 'discouraged',
        ];
    }

    public function parseAndValidateAssertionResponse(
        string $assertionResponseJson,
        array $allowedCredentials,
        string $challenge,
        ServerRequestInterface $request
    ): void {
        $assertionCredential = $this->getAssertionCredential($assertionResponseJson);

        if ($allowedCredentials !== []) {
            Assert::true($this->isCredentialIdAllowed($assertionCredential['rawId'], $allowedCredentials));
        }

        $authenticatorData = $this->getAuthenticatorData($assertionCredential['response']['authenticatorData']);

        $clientData = $this->getCollectedClientData($assertionCredential['response']['clientDataJSON']);
        Assert::same($clientData['type'], 'webauthn.get');

        try {
            $knownChallenge = sodium_base642bin($challenge, SODIUM_BASE64_VARIANT_ORIGINAL);
            $cDataChallenge = sodium_base642bin($clientData['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (SodiumException $exception) {
            throw new WebAuthnException((string) $exception);
        }

        Assert::true(hash_equals($knownChallenge, $cDataChallenge));

        $host = $request->getUri()->getHost();
        Assert::same($host, parse_url($clientData['origin'], PHP_URL_HOST));

        $rpIdHash = hash('sha256', $host, true);
        Assert::true(hash_equals($rpIdHash, $authenticatorData['rpIdHash']));

        $isUserPresent = (ord($authenticatorData['flags']) & 1) !== 0;
        Assert::true($isUserPresent);
    }

    public function parseAndValidateAttestationResponse(
        string $attestationResponse,
        string $credentialCreationOptions,
        ServerRequestInterface $request
    ): array {
        try {
            $attestationCredential = $this->getAttestationCredential($attestationResponse);
        } catch (Throwable $exception) {
            throw new WebAuthnException('Invalid authenticator response.', (int) $exception->getCode(), $exception);
        }

        $creationOptions = json_decode($credentialCreationOptions, true);
        Assert::isArray($creationOptions);
        Assert::keyExists($creationOptions, 'challenge');
        Assert::string($creationOptions['challenge']);
        Assert::keyExists($creationOptions, 'user');
        Assert::isArray($creationOptions['user']);
        Assert::keyExists($creationOptions['user'], 'id');
        Assert::string($creationOptions['user']['id']);

        $clientData = $this->getCollectedClientData($attestationCredential['response']['clientDataJSON']);

        // Verify that the value of C.type is webauthn.create.
        Assert::same($clientData['type'], 'webauthn.create');

        // Verify that the value of C.challenge equals the base64url encoding of options.challenge.
        $optionsChallenge = sodium_base642bin($creationOptions['challenge'], SODIUM_BASE64_VARIANT_ORIGINAL);
        $clientDataChallenge = sodium_base642bin($clientData['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        Assert::true(hash_equals($optionsChallenge, $clientDataChallenge));

        // Verify that the value of C.origin matches the Relying Party's origin.
        $host = $request->getUri()->getHost();
        Assert::same($host, parse_url($clientData['origin'], PHP_URL_HOST), 'Invalid origin.');

        // Perform CBOR decoding on the attestationObject field.
        $attestationObject = $this->getAttestationObject($attestationCredential['response']['attestationObject']);

        $authenticatorData = $this->getAuthenticatorData($attestationObject['authData']);
        Assert::notNull($authenticatorData['attestedCredentialData']);

        // Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
        $rpIdHash = hash('sha256', $host, true);
        Assert::true(hash_equals($rpIdHash, $authenticatorData['rpIdHash']), 'Invalid rpIdHash.');

        // Verify that the User Present bit of the flags in authData is set.
        $isUserPresent = (ord($authenticatorData['flags']) & 1) !== 0;
        Assert::true($isUserPresent);

        Assert::same($attestationObject['fmt'], 'none');
        Assert::same($attestationObject['attStmt'], []);

        $encodedCredentialId = sodium_bin2base64(
            $authenticatorData['attestedCredentialData']['credentialId'],
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
        $encodedCredentialPublicKey = sodium_bin2base64(
            $authenticatorData['attestedCredentialData']['credentialPublicKey'],
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
        $userHandle = sodium_bin2base64(
            sodium_base642bin($creationOptions['user']['id'], SODIUM_BASE64_VARIANT_ORIGINAL),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );

        return [
            'publicKeyCredentialId' => $encodedCredentialId,
            'type' => 'public-key',
            'transports' => [],
            'attestationType' => $attestationObject['fmt'],
            'aaguid' => $authenticatorData['attestedCredentialData']['aaguid'],
            'credentialPublicKey' => $encodedCredentialPublicKey,
            'userHandle' => $userHandle,
            'counter' => $authenticatorData['signCount'],
        ];
    }

    /**
     * In order to prevent replay attacks, the challenges MUST contain enough entropy to make guessing them infeasible.
     * Challenges SHOULD therefore be at least 16 bytes long.
     *
     * @see https://www.w3.org/TR/webauthn-3/#sctn-cryptographic-challenges
     *
     * @psalm-return non-empty-string
     *
     * @throws WebAuthnException
     */
    private function generateChallenge(): string
    {
        try {
            return sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (Throwable $throwable) { // @codeCoverageIgnore
            throw new WebAuthnException('Error when generating challenge.'); // @codeCoverageIgnore
        }
    }

    /**
     * @see https://www.w3.org/TR/webauthn-3/#sctn-authenticator-data
     *
     * @psalm-return array{
     *   rpIdHash: string,
     *   flags: string,
     *   signCount: int,
     *   attestedCredentialData: array{
     *     aaguid: string,
     *     credentialId: string,
     *     credentialPublicKey: string,
     *     credentialPublicKeyDecoded: mixed[]
     *   }|null,
     *   extensions: string|null
     * }
     *
     * @throws WebAuthnException
     */
    private function getAuthenticatorData(string $authData): array
    {
        $authDataLength = mb_strlen($authData, '8bit');
        Assert::true($authDataLength >= 37);
        $authDataStream = new DataStream($authData);

        $rpIdHash = $authDataStream->take(32);
        $flags = $authDataStream->take(1);

        // 32-bit unsigned big-endian integer
        $unpackedSignCount = unpack('N', $authDataStream->take(4));
        Assert::isArray($unpackedSignCount);
        Assert::keyExists($unpackedSignCount, 1);
        Assert::integer($unpackedSignCount[1]);
        $signCount = $unpackedSignCount[1];

        $attestedCredentialData = null;
        // Bit 6: Attested credential data included (AT).
        if ((ord($flags) & 64) !== 0) {
            /** Authenticator Attestation GUID */
            $aaguid = $authDataStream->take(16);

            // 16-bit unsigned big-endian integer
            $unpackedCredentialIdLength = unpack('n', $authDataStream->take(2));
            Assert::isArray($unpackedCredentialIdLength);
            Assert::keyExists($unpackedCredentialIdLength, 1);
            Assert::integer($unpackedCredentialIdLength[1]);
            $credentialIdLength = $unpackedCredentialIdLength[1];

            $credentialId = $authDataStream->take($credentialIdLength);

            $credentialPublicKeyDecoded = (new CBORDecoder())->decode($authDataStream);
            Assert::isArray($credentialPublicKeyDecoded);
            $credentialPublicKey = mb_substr(
                $authData,
                37 + 18 + $credentialIdLength,
                $authDataStream->getPosition(),
                '8bit'
            );

            $attestedCredentialData = [
                'aaguid' => $aaguid,
                'credentialId' => $credentialId,
                'credentialPublicKey' => $credentialPublicKey,
                'credentialPublicKeyDecoded' => $credentialPublicKeyDecoded,
            ];
        }

        return [
            'rpIdHash' => $rpIdHash,
            'flags' => $flags,
            'signCount' => $signCount,
            'attestedCredentialData' => $attestedCredentialData,
            'extensions' => null,
        ];
    }

    /**
     * @psalm-param non-empty-string $id
     * @psalm-param list<array{id: non-empty-string, type: non-empty-string}> $allowedCredentials
     *
     * @throws WebAuthnException
     */
    private function isCredentialIdAllowed(string $id, array $allowedCredentials): bool
    {
        foreach ($allowedCredentials as $credential) {
            try {
                $credentialId = sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            } catch (SodiumException $exception) {
                throw new WebAuthnException();
            }

            if (hash_equals($credentialId, $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see https://www.iana.org/assignments/cose/cose.xhtml#algorithms
     *
     * @psalm-return list<array{alg: int, type: 'public-key'}>
     */
    private function getCredentialParameters(): array
    {
        return [
            ['alg' => -257, 'type' => 'public-key'], // RS256
            ['alg' => -259, 'type' => 'public-key'], // RS512
            ['alg' => -37, 'type' => 'public-key'], // PS256
            ['alg' => -39, 'type' => 'public-key'], // PS512
            ['alg' => -7, 'type' => 'public-key'], // ES256
            ['alg' => -36, 'type' => 'public-key'], // ES512
            ['alg' => -8, 'type' => 'public-key'], // EdDSA
        ];
    }

    /**
     * @psalm-param non-empty-string $assertionResponseJson
     *
     * @psalm-return array{
     *   id: non-empty-string,
     *   type: 'public-key',
     *   rawId: non-empty-string,
     *   response: array{
     *     clientDataJSON: non-empty-string,
     *     authenticatorData: non-empty-string,
     *     signature: non-empty-string,
     *   }
     * }
     *
     * @throws SodiumException
     * @throws InvalidArgumentException
     */
    private function getAssertionCredential(string $assertionResponseJson): array
    {
        $credential = json_decode($assertionResponseJson, true);
        Assert::isArray($credential);
        Assert::keyExists($credential, 'id');
        Assert::stringNotEmpty($credential['id']);
        Assert::keyExists($credential, 'type');
        Assert::same($credential['type'], 'public-key');
        Assert::keyExists($credential, 'rawId');
        Assert::stringNotEmpty($credential['rawId']);
        Assert::keyExists($credential, 'response');
        Assert::isArray($credential['response']);
        Assert::keyExists($credential['response'], 'clientDataJSON');
        Assert::stringNotEmpty($credential['response']['clientDataJSON']);
        Assert::keyExists($credential['response'], 'authenticatorData');
        Assert::stringNotEmpty($credential['response']['authenticatorData']);
        Assert::keyExists($credential['response'], 'signature');
        Assert::stringNotEmpty($credential['response']['signature']);

        $id = sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $rawId = sodium_base642bin($credential['rawId'], SODIUM_BASE64_VARIANT_ORIGINAL);
        Assert::stringNotEmpty($id);
        Assert::stringNotEmpty($rawId);
        Assert::true(hash_equals($rawId, $id));

        $clientDataJSON = sodium_base642bin($credential['response']['clientDataJSON'], SODIUM_BASE64_VARIANT_ORIGINAL);
        Assert::stringNotEmpty($clientDataJSON);
        $authenticatorData = sodium_base642bin(
            $credential['response']['authenticatorData'],
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
        Assert::stringNotEmpty($authenticatorData);
        $signature = sodium_base642bin($credential['response']['signature'], SODIUM_BASE64_VARIANT_ORIGINAL);
        Assert::stringNotEmpty($signature);

        return [
            'id' => $credential['id'],
            'type' => 'public-key',
            'rawId' => $rawId,
            'response' => [
                'clientDataJSON' => $clientDataJSON,
                'authenticatorData' => $authenticatorData,
                'signature' => $signature,
            ],
        ];
    }

    /**
     * @see https://www.w3.org/TR/webauthn-3/#iface-authenticatorattestationresponse
     *
     * @psalm-param non-empty-string $attestationResponse
     *
     * @psalm-return array{
     *   id: non-empty-string,
     *   rawId: non-empty-string,
     *   type: 'public-key',
     *   response: array{clientDataJSON: non-empty-string, attestationObject: non-empty-string}
     * }
     *
     * @throws SodiumException
     * @throws InvalidArgumentException
     */
    private function getAttestationCredential(string $attestationResponse): array
    {
        $credential = json_decode($attestationResponse, true);
        Assert::isArray($credential);
        Assert::keyExists($credential, 'id');
        Assert::stringNotEmpty($credential['id']);
        Assert::keyExists($credential, 'rawId');
        Assert::stringNotEmpty($credential['rawId']);
        Assert::keyExists($credential, 'type');
        Assert::string($credential['type']);
        Assert::same($credential['type'], 'public-key');
        Assert::keyExists($credential, 'response');
        Assert::isArray($credential['response']);
        Assert::keyExists($credential['response'], 'clientDataJSON');
        Assert::stringNotEmpty($credential['response']['clientDataJSON']);
        Assert::keyExists($credential['response'], 'attestationObject');
        Assert::stringNotEmpty($credential['response']['attestationObject']);

        $id = sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $rawId = sodium_base642bin($credential['rawId'], SODIUM_BASE64_VARIANT_ORIGINAL);
        Assert::stringNotEmpty($id);
        Assert::stringNotEmpty($rawId);
        Assert::true(hash_equals($rawId, $id));

        $clientDataJSON = sodium_base642bin($credential['response']['clientDataJSON'], SODIUM_BASE64_VARIANT_ORIGINAL);
        Assert::stringNotEmpty($clientDataJSON);
        $attestationObject = sodium_base642bin(
            $credential['response']['attestationObject'],
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
        Assert::stringNotEmpty($attestationObject);

        return [
            'id' => $credential['id'],
            'rawId' => $rawId,
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $clientDataJSON,
                'attestationObject' => $attestationObject,
            ],
        ];
    }

    /**
     * @see https://www.w3.org/TR/webauthn-3/#dictionary-client-data
     *
     * @psalm-param non-empty-string $clientDataJSON
     *
     * @return array{
     *   type: 'webauthn.create'|'webauthn.get',
     *   challenge: non-empty-string,
     *   origin: non-empty-string
     * }
     */
    private function getCollectedClientData(string $clientDataJSON): array
    {
        $clientData = json_decode($clientDataJSON, true);

        Assert::isArray($clientData);
        Assert::keyExists($clientData, 'type');
        Assert::stringNotEmpty($clientData['type']);
        Assert::inArray($clientData['type'], ['webauthn.create', 'webauthn.get']);
        Assert::keyExists($clientData, 'challenge');
        Assert::stringNotEmpty($clientData['challenge']);
        Assert::keyExists($clientData, 'origin');
        Assert::stringNotEmpty($clientData['origin']);

        return [
            'type' => $clientData['type'],
            'challenge' => $clientData['challenge'],
            'origin' => $clientData['origin'],
        ];
    }

    /**
     * @psalm-param non-empty-string $attestationObjectEncoded
     *
     * @psalm-return array{fmt: string, attStmt: mixed[], authData: string}
     *
     * @throws WebAuthnException
     */
    private function getAttestationObject(string $attestationObjectEncoded): array
    {
        $decoded = (new CBORDecoder())->decode(new DataStream($attestationObjectEncoded));

        Assert::isArray($decoded);
        Assert::keyExists($decoded, 'fmt');
        Assert::string($decoded['fmt']);
        Assert::keyExists($decoded, 'attStmt');
        Assert::isArray($decoded['attStmt']);
        Assert::keyExists($decoded, 'authData');
        Assert::string($decoded['authData']);

        return $decoded;
    }
}
