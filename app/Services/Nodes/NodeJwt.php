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

final readonly class NodeJwt
{
    /**
     * A token signed with the node's key that Wings accepts for the given scope. A user adds
     * their UUID as the "user_uuid" claim.
     *
     * @param  JwtClaims  $claims
     */
    public function issue(Node $node, JwtScope $scope, string $identifiedBy, DateTimeImmutable $expiresAt, ?User $user = null, array $claims = [], ?string $subject = null): UnencryptedToken
    {
        $identifier = hash('sha256', $identifiedBy);

        // The key is random and the address has a literal "://", so neither is ever empty;
        // the checks only stop an empty one from signing a token.
        $key = $node->getDecryptedKey();
        throw_if($key === '', InvalidArgumentException::class, 'Cannot generate a JWT for a node with an empty signing key.');

        $audience = $node->getConnectionAddress();
        throw_if($audience === '', InvalidArgumentException::class, 'Cannot generate a JWT for a node with an empty connection address.');

        $issuer = config()->string('app.url');
        throw_if($issuer === '', InvalidArgumentException::class, 'Cannot generate a JWT without an application URL.');

        $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText($key));

        $builder = $config->builder(new TimestampDates)
            ->issuedBy($issuer)
            ->permittedFor($audience)
            ->identifiedBy($identifier)
            ->withHeader('jti', $identifier)
            ->issuedAt(CarbonImmutable::now())
            ->canOnlyBeUsedAfter(CarbonImmutable::now()->subMinutes(5))
            ->expiresAt($expiresAt);

        if ($subject !== null && $subject !== '') {
            $builder = $builder->relatedTo($subject)->withHeader('sub', $subject);
        }

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        $builder = $builder->withClaim('scope', $scope->value);

        if ($user instanceof User) {
            $builder = $builder->withClaim('user_uuid', $user->uuid);
        }

        return $builder
            ->withClaim('unique_id', Str::random())
            ->getToken($config->signer(), $config->signingKey());
    }
}
