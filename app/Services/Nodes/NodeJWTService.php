<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Nodes;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Extensions\Lcobucci\JWT\Encoding\TimestampDates;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

class NodeJWTService
{
    /** @var JwtClaims */
    private array $claims = [];

    /** @var list<JwtScope> */
    private array $scopes;

    private ?User $user = null;

    private DateTimeImmutable $expiresAt;

    private ?string $subject = null;

    /**
     * Set the claims to include in this JWT.
     *
     * @param  JwtClaims  $claims
     */
    public function setClaims(array $claims): self
    {
        $this->claims = $claims;

        return $this;
    }

    public function setScopes(JwtScope ...$scopes): self
    {
        $this->scopes = array_values($scopes);

        return $this;
    }

    /**
     * Attaches a user to the JWT being created and will automatically inject the
     * "user_uuid" key into the final claims array with the user's UUID.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setExpiresAt(DateTimeImmutable $date): self
    {
        $this->expiresAt = $date;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Generate a new JWT for a given node.
     */
    public function handle(Node $node, ?string $identifiedBy): UnencryptedToken
    {
        $identifier = hash('sha256', $identifiedBy ?? '');

        // The daemon key is a decrypted, randomly generated token (see
        // Node::DAEMON_TOKEN_LENGTH) and the connection address is built from a
        // literal "://" separator, so neither is ever empty in practice. Guard
        // explicitly anyway rather than trusting that invariant blindly.
        $nodeKey = $node->getDecryptedKey();
        throw_if($nodeKey === '', InvalidArgumentException::class, 'Cannot generate a JWT for a node with an empty signing key.');

        $connectionAddress = $node->getConnectionAddress();
        throw_if($connectionAddress === '', InvalidArgumentException::class, 'Cannot generate a JWT for a node with an empty connection address.');

        $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText($nodeKey));

        $builder = $config->builder(new TimestampDates)
            ->issuedBy(JsonValueGuard::nonEmptyString(config('app.url')))
            ->permittedFor($connectionAddress)
            ->identifiedBy($identifier)
            ->withHeader('jti', $identifier)
            ->issuedAt(CarbonImmutable::now())
            ->canOnlyBeUsedAfter(CarbonImmutable::now()->subMinutes(5));

        if (isset($this->expiresAt)) {
            $builder = $builder->expiresAt($this->expiresAt);
        }

        if (! empty($this->subject)) {
            $builder = $builder->relatedTo($this->subject)->withHeader('sub', $this->subject);
        }

        foreach ($this->claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        throw_if($this->scopes === [], InvalidArgumentException::class, 'Cannot generate a JWT without providing at least one scope.');

        $builder = $builder->withClaim('scope', implode(' ', array_map(fn (JwtScope $scope): string => $scope->value, $this->scopes)));

        if ($this->user instanceof User) {
            $builder = $builder->withClaim('user_uuid', $this->user->uuid);
        }

        return $builder
            ->withClaim('unique_id', Str::random())
            ->getToken($config->signer(), $config->signingKey());
    }
}
