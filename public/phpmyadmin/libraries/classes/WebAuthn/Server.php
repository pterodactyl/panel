<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Web Authentication API server.
 *
 * @see https://www.w3.org/TR/webauthn-3/
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API
 * @see https://webauthn.guide/
 */
interface Server
{
    /**
     * @psalm-return array{
     *   challenge: non-empty-string,
     *   rp: array{name: string, id: string},
     *   user: array{id: string, name: string, displayName: string},
     *   pubKeyCredParams: list<array{alg: int, type: 'public-key'}>,
     *   authenticatorSelection: array<string, string>,
     *   timeout: positive-int,
     *   attestation: non-empty-string
     * }
     *
     * @throws WebAuthnException
     */
    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array;

    /**
     * @psalm-param list<array{id: non-empty-string, type: non-empty-string}> $allowedCredentials
     *
     * @return array<string, array<int, array<string, string>>|int|string>
     *
     * @throws WebAuthnException
     */
    public function getCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        array $allowedCredentials
    ): array;

    /**
     * @see https://www.w3.org/TR/webauthn-3/#sctn-verifying-assertion
     *
     * @psalm-param non-empty-string $assertionResponseJson
     * @psalm-param list<array{id: non-empty-string, type: non-empty-string}> $allowedCredentials
     * @psalm-param non-empty-string $challenge
     *
     * @throws WebAuthnException
     */
    public function parseAndValidateAssertionResponse(
        string $assertionResponseJson,
        array $allowedCredentials,
        string $challenge,
        ServerRequestInterface $request
    ): void;

    /**
     * @see https://www.w3.org/TR/webauthn-3/#sctn-registering-a-new-credential
     *
     * @psalm-param non-empty-string $attestationResponse
     * @psalm-param non-empty-string $credentialCreationOptions
     *
     * @return mixed[]
     *
     * @throws WebAuthnException
     */
    public function parseAndValidateAttestationResponse(
        string $attestationResponse,
        string $credentialCreationOptions,
        ServerRequestInterface $request
    ): array;
}
