<?php

namespace Pterodactyl\Models;

use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialDescriptor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SecurityKey extends Model
{
    use HasFactory;

    public const RESOURCE_NAME = 'security_key';

    protected $casts = [
        'user_id' => 'int',
        'transports' => 'array',
        'trust_path' => 'array',
        'other_ui' => 'array',
    ];

    protected $guarded = [
        'uuid',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toCredentialsDescriptor()
    {
        return new PublicKeyCredentialDescriptor(
            $this->type,
            $this->public_key_id,
            $this->transports
        );
    }

    public function toCredentialSource(): PublicKeyCredentialSource
    {
        return PublicKeyCredentialSource::createFromArray([
            'publicKeyCredentialId' => $this->public_key_id,
            'type' => $this->type,
            'transports' => $this->transports,
            'attestationType' => $this->attestation_type,
            // 'trustPath' => $key->trustPath->jsonSerialize(),
            'aaguid' => $this->aaguid,
            'credentialPublicKey' => $this->public_key,
            'userHandle' => $this->user_handle,
            'counter' => $this->counter,
            'otherUI' => $this->other_ui,
        ]);
    }
}
