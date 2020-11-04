<?php

namespace Pterodactyl\Services\Nodes;

use DateTimeInterface;
use Lcobucci\JWT\Builder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Lcobucci\JWT\Signer\Key;
use Pterodactyl\Models\Node;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class NodeJWTService
{
    /**
     * @var array
     */
    private $claims = [];

    /**
     * @var int|null
     */
    private $expiresAt;

    /**
     * Set the claims to include in this JWT.
     *
     * @param array $claims
     * @return $this
     */
    public function setClaims(array $claims)
    {
        $this->claims = $claims;

        return $this;
    }

    public function setExpiresAt(DateTimeInterface $date)
    {
        $this->expiresAt = $date->getTimestamp();

        return $this;
    }

    /**
     * Generate a new JWT for a given node.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param string|null $identifiedBy
     * @return \Lcobucci\JWT\Token
     */
    public function handle(Node $node, string $identifiedBy)
    {
        $signer = new Sha256;

        $builder = (new Builder)->issuedBy(config('app.url'))
            ->permittedFor($node->getConnectionAddress())
            ->identifiedBy(md5($identifiedBy), true)
            ->issuedAt(CarbonImmutable::now()->getTimestamp())
            ->canOnlyBeUsedAfter(CarbonImmutable::now()->subMinutes(5)->getTimestamp());

        if ($this->expiresAt) {
            $builder = $builder->expiresAt($this->expiresAt);
        }

        foreach ($this->claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->withClaim('unique_id', Str::random(16))
            ->getToken($signer, new Key($node->getDecryptedKey()));
    }
}
