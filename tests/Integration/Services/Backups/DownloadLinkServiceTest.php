<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Backups\DownloadLinkServiceTest;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Pterodactyl\Contracts\Backups\GeneratesBackupDownloadLinks;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Models\Backup;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
test('it generates local url with jwt', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->for($server)->create(['disk' => Backup::ADAPTER_WINGS]);
    $url = $this->app->make(GeneratesBackupDownloadLinks::class)->generate($backup, $server->user);
    expect($url)->toStartWith($prefix = $server->node->getConnectionAddress().'/download/backup?token=');
    $config = Configuration::forSymmetricSigner(new Sha256(), $key = InMemory::plainText($server->node->getDecryptedKey()));
    $config = $config->withValidationConstraints(new SignedWith(new Sha256(), $key));
    /** @var Plain $token */
    $token = $config->parser()->parse(mb_substr($url, mb_strlen($prefix)));
    expect($config->validator()->validate($token, ...$config->validationConstraints()))->toBeTrue('Failed to validate that the JWT data returned was signed using the Node\'s secret key.');
    $timestamp = CarbonImmutable::createFromTimestamp(CarbonImmutable::now()->getTimestamp())->timezone('UTC');
    // Check that the claims are generated correctly.
    expect($token->hasBeenIssuedBy(config('app.url')))->toBeTrue();
    expect($token->isPermittedFor($server->node->getConnectionAddress()))->toBeTrue();
    expect($token->claims()->get('iat'))->toEqual($timestamp);
    expect($token->claims()->get('nbf'))->toEqual($timestamp->subMinutes(5));
    expect($token->claims()->get('exp'))->toEqual($timestamp->addMinutes(15));
    expect($token->claims()->get('backup_uuid'))->toBe($backup->uuid);
    expect($token->claims()->get('server_uuid'))->toBe($server->uuid);
    expect($token->claims()->get('scope'))->toEqual(JwtScope::BackupDownload->value);
});
