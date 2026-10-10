<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionUrlDownloaderTest;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionUrlDownloader;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
uses(WithFaker::class);

beforeEach(function (): void {
    $this->keypair = sodium_crypto_sign_keypair();
    $this->base = mb_rtrim($this->faker->url(), '/').'/';
    $this->other = mb_rtrim($this->faker->url(), '/').'/';
    config(['extensions.signed_urls.public_key' => base64_encode(sodium_crypto_sign_publickey($this->keypair))]);
    $this->downloader = $this->app->make(ExtensionUrlDownloader::class);
});

/**
 * @param  array{iss?: string, aud?: string, exp?: CarbonImmutable|null, url?: string|null}  $claims
 */
function signed(string $keypair, string $prefix, array $claims = []): string
{
    $key = InMemory::plainText(sodium_crypto_sign_secretkey($keypair));
    $builder = Configuration::forAsymmetricSigner(new Eddsa, $key, $key)->builder()
        ->issuedBy($claims['iss'] ?? config('extensions.signed_urls.issuer'))
        ->permittedFor($claims['aud'] ?? config('extensions.signed_urls.audience'))
        ->relatedTo('1234');

    $expires = array_key_exists('exp', $claims) ? $claims['exp'] : CarbonImmutable::now()->addMinutes(15);
    if ($expires instanceof CarbonImmutable) {
        $builder = $builder->expiresAt($expires);
    }

    if (($claims['url'] ?? null) !== null) {
        $builder = $builder->withClaim('url', $claims['url']);
    }

    return $prefix.$builder->getToken(new Eddsa, $key)->toString();
}

test('a valid token is accepted', function (): void {
    $source = signed($this->keypair, $this->base, ['url' => $this->base]);

    expect($this->downloader->verifiedUrl($source))->toBe($source);
});

test('the signed claim wins over the host it was handed with', function (): void {
    $source = signed($this->keypair, $this->other, ['url' => $this->base]);

    expect($this->downloader->verifiedUrl($source))->toStartWith($this->base);
});

test('a valid token is refused when disabled', function (): void {
    config(['extensions.signed_urls.enabled' => false]);
    Http::fake();

    expect(fn () => $this->downloader->download(signed($this->keypair, $this->base, ['url' => $this->base])))
        ->toThrow(InvalidExtensionException::class, 'disabled on this panel');
    Http::assertNothingSent();
});

test('a token signed with another key is refused', function (): void {
    $this->downloader->verifiedUrl(signed(sodium_crypto_sign_keypair(), $this->base, ['url' => $this->base]));
})->throws(InvalidExtensionException::class, 'not a signed install URL');

test('a source that carries no token is refused', function (): void {
    $this->downloader->verifiedUrl($this->other.'extension.pteroext');
})->throws(InvalidExtensionException::class, 'not a signed install URL');

test('a tampered token is refused', function (): void {
    $source = signed($this->keypair, $this->base, ['url' => $this->base]);
    [$header, $claims, $signature] = explode('.', mb_substr($source, mb_strlen($this->base)));
    $forged = json_decode((string) base64_decode(strtr($claims, '-_', '+/'), true), true);
    $forged['url'] = $this->other;
    $claims = mb_rtrim(strtr(base64_encode((string) json_encode($forged)), '+/', '-_'), '=');

    $this->downloader->verifiedUrl($this->base."{$header}.{$claims}.{$signature}");
})->throws(InvalidExtensionException::class, 'not a signed install URL');

test('an expired token is refused', function (): void {
    $this->downloader->verifiedUrl(signed($this->keypair, $this->base, ['url' => $this->base, 'exp' => CarbonImmutable::now()->subSecond()]));
})->throws(InvalidExtensionException::class, 'expired');

test('a token without an expiry is refused', function (): void {
    $this->downloader->verifiedUrl(signed($this->keypair, $this->base, ['url' => $this->base, 'exp' => null]));
})->throws(InvalidExtensionException::class, 'expired');

test('a signed token for another purpose is refused', function (): void {
    $this->downloader->verifiedUrl(signed($this->keypair, $this->base, ['url' => $this->base, 'aud' => 'something-else']));
})->throws(InvalidExtensionException::class, 'not an extension install URL');

test('a token without a source claim is refused', function (): void {
    $this->downloader->verifiedUrl(signed($this->keypair, $this->base));
})->throws(InvalidExtensionException::class, 'does not say where to download');

test('it downloads the package with the install header', function (): void {
    Http::fake([$this->base.'*' => Http::response('package-bytes')]);

    $path = $this->downloader->download(signed($this->keypair, $this->base, ['url' => $this->base]));

    try {
        expect(File::get($path))->toBe('package-bytes');
        Http::assertSent(fn ($request): bool => $request->hasHeader(ExtensionUrlDownloader::INSTALL_HEADER));
    } finally {
        File::delete($path);
    }
});

test('nothing is requested for a token that does not verify', function (): void {
    Http::fake();

    expect(fn () => $this->downloader->download(signed(sodium_crypto_sign_keypair(), $this->base, ['url' => $this->base])))
        ->toThrow(InvalidExtensionException::class);
    Http::assertNothingSent();
});

test('a refusal from the server is reported and leaves no file behind', function (): void {
    Http::fake([$this->base.'*' => Http::response(['error' => ['message' => 'Refused.']], 403)]);
    $before = File::glob(storage_path('app/extensions-tmp/*.pteroext'));

    expect(fn () => $this->downloader->download(signed($this->keypair, $this->base, ['url' => $this->base])))
        ->toThrow(InvalidExtensionException::class, 'Refused.');
    expect(File::glob(storage_path('app/extensions-tmp/*.pteroext')))->toBe($before);
});
