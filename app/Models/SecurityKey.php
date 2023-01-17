<?php

namespace Pterodactyl\Models;

use Illuminate\Http\Request;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\TrustPath;
use Symfony\Component\Uid\NilUuid;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Uid\AbstractUid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPathLoader;
use Webauthn\PublicKeyCredentialDescriptor;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $name
 * @property string $public_key_id
 * @property string $public_key
 * @property AbstractUid $aaguid
 * @property string $type
 * @property string[] $transports
 * @property string $attestation_type
 * @property \Webauthn\TrustPath\TrustPath $trust_path
 * @property string $user_handle
 * @property int $counter
 * @property array<string, mixed>|null $other_ui
 *
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
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

    public function publicKey(): Attribute
    {
        return new Attribute(
            get: fn (string $value) => base64_decode($value),
            set: fn (string $value) => base64_encode($value),
        );
    }

    public function publicKeyId(): Attribute
    {
        return new Attribute(
            get: fn (string $value) => base64_decode($value),
            set: fn (string $value) => base64_encode($value),
        );
    }

    public function aaguid(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value): AbstractUid => is_null($value) ? new NilUuid() : Uuid::fromString($value),
            set: fn (AbstractUid|null $value): string|null => (is_null($value) || $value instanceof NilUuid) ? null : $value->__toString(),
        );
    }

    public function trustPath(): Attribute
    {
        return new Attribute(
            get: fn (mixed $value) => is_null($value) ? null : TrustPathLoader::loadTrustPath(json_decode($value, true)),
            set: fn (TrustPath|null $value) => json_encode($value),
        );
    }

    public function getPublicKeyCredentialDescriptor(): PublicKeyCredentialDescriptor
    {
        return new PublicKeyCredentialDescriptor($this->type, $this->public_key_id, $this->transports);
    }

    public function getPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $this->public_key_id,
            $this->type,
            $this->transports,
            $this->attestation_type,
            $this->trust_path,
            $this->aaguid,
            $this->public_key,
            $this->user_handle,
            $this->counter
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns a PSR17 Request factory to be used by different Webauthn tooling.
     */
    public static function getPsrRequestFactory(Request $request): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        $httpFactory = new PsrHttpFactory($factory, $factory, $factory, $factory);

        return $httpFactory->createRequest($request);
    }
}
