<?php

namespace Pterodactyl\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Ramsey\Uuid\UuidInterface;
use Webauthn\TrustPath\TrustPath;
use Nyholm\Psr7\Factory\Psr17Factory;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPathLoader;
use Webauthn\PublicKeyCredentialDescriptor;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class SecurityKey extends Model
{
    use HasFactory;

    public const RESOURCE_NAME = 'security_key';
    public const PK_SESSION_NAME = 'security_key_pk_request';

    protected $casts = [
        'user_id' => 'int',
        'transports' => 'array',
        'other_ui' => 'array',
    ];

    protected $guarded = [
        'uuid',
        'user_id',
    ];

    public function getPublicKeyAttribute(string $value): string
    {
        return base64_decode($value);
    }

    public function setPublicKeyAttribute(string $value): void
    {
        $this->attributes['public_key'] = base64_encode($value);
    }

    public function getPublicKeyIdAttribute(string $value): string
    {
        return base64_decode($value);
    }

    public function setPublicKeyIdAttribute(string $value): void
    {
        $this->attributes['public_key_id'] = base64_encode($value);
    }

    public function getTrustPathAttribute(?string $value): ?TrustPath
    {
        if (is_null($value)) {
            return null;
        }

        return TrustPathLoader::loadTrustPath(json_decode($value, true));
    }

    public function setTrustPathAttribute(?TrustPath $value): void
    {
        $this->attributes['trust_path'] = json_encode($value);
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface|string|null $value
     */
    public function setAaguidAttribute($value): void
    {
        $value = $value instanceof UuidInterface ? $value->__toString() : $value;

        $this->attributes['aaguid'] = (is_null($value) || $value === Uuid::NIL) ? null : $value;
    }

    public function getAaguidAttribute(?string $value): ?UuidInterface
    {
        if (!is_null($value) && Uuid::isValid($value)) {
            return Uuid::fromString($value);
        }

        return null;
    }

    public function getPublicKeyCredentialDescriptor(): PublicKeyCredentialDescriptor
    {
        return new PublicKeyCredentialDescriptor(
            $this->type,
            $this->public_key_id,
            $this->transports
        );
    }

    public function getPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $this->public_key_id,
            $this->type,
            $this->transports,
            $this->attestation_type,
            $this->trust_path,
            $this->aaguid ?? Uuid::fromString(Uuid::NIL),
            $this->public_key,
            (string) $this->user_id,
            $this->counter
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns a PSR17 Request factory to be used by different Webauthn tooling.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function getPsrRequestFactory(Request $request): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        $httpFactory = new PsrHttpFactory($factory, $factory, $factory, $factory);

        return $httpFactory->createRequest($request);
    }
}
