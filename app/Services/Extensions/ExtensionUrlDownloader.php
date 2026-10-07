<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Carbon\CarbonImmutable;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Throwable;

/**
 * Downloads extension packages from signed install URLs.
 *
 * A URL ends in a JWT signed with Pterodactyl's Ed25519 key. Nothing is fetched until
 * that signature verifies against the public key shipped in config/extensions.php, and the
 * address fetched is the one inside the signed token rather than the one that was typed. A
 * pasted command therefore cannot make a panel install a package from anywhere else.
 */
class ExtensionUrlDownloader
{
    public const string INSTALL_HEADER = 'X-Pterodactyl-Install';

    public const int MAX_PACKAGE_BYTES = 100 * 1024 * 1024;

    /** Whether a package source is a URL, and so has to be a signed install URL. */
    public function isUrl(string $source): bool
    {
        return preg_match('#^https?://#i', $source) === 1;
    }

    /**
     * Download the package a signed install URL points at and return the path of the archive.
     * The caller owns the file and deletes it once the package is installed.
     *
     * @throws InvalidExtensionException
     */
    public function download(string $link): string
    {
        $url = $this->verifiedUrl($link);
        $path = storage_path('app'.DIRECTORY_SEPARATOR.'extensions-tmp'.DIRECTORY_SEPARATOR.Str::random(12).'.pteroext');
        File::ensureDirectoryExists(dirname($path));

        try {
            $response = Http::withHeaders([self::INSTALL_HEADER => (string) config('extensions.panel_version')])
                ->connectTimeout(10)
                ->timeout(120)
                ->sink($path)
                ->withOptions(['on_headers' => $this->assertWithinSizeLimit(...)])
                ->get($url);

            throw_unless($response->successful(), InvalidExtensionException::class, $this->failureMessage($path, $response->status()));
            throw_if(File::size($path) > self::MAX_PACKAGE_BYTES, InvalidExtensionException::class, 'The extension package is larger than the panel accepts.');
        } catch (Throwable $throwable) {
            rescue(fn () => File::delete($path));

            // Guzzle wraps whatever the on_headers check throws.
            $refusal = $throwable instanceof InvalidExtensionException ? $throwable : $throwable->getPrevious();
            throw_if($refusal instanceof InvalidExtensionException, $refusal);
            throw_unless($throwable instanceof ConnectionException || $throwable instanceof TransferException, $throwable);

            throw new InvalidExtensionException('Could not download the extension. Please try again.', previous: $throwable);
        }

        return $path;
    }

    /**
     * The address to fetch a package from, taken from the URL's signed token.
     *
     * @throws InvalidExtensionException
     */
    public function verifiedUrl(string $link): string
    {
        throw_unless((bool) config('extensions.signed_urls.enabled'), InvalidExtensionException::class, 'Installing extensions from a URL is disabled on this panel.');
        throw_unless(extension_loaded('sodium'), InvalidExtensionException::class, 'The PHP sodium extension is required to verify signed install URLs.');

        $jwt = Str::afterLast((string) preg_replace('/[?#].*$/', '', trim($link)), '/');

        $token = null;

        try {
            $token = (new Parser(new JoseEncoder))->parse($jwt);
            $key = InMemory::base64Encoded((string) config('extensions.signed_urls.public_key'));
            $signed = $token instanceof UnencryptedToken && (new Validator)->validate($token, new SignedWith(new Eddsa, $key));
        } catch (Throwable) {
            $signed = false;
        }

        // Nothing in the token is trusted, or even reported back, before its signature verifies.
        throw_unless(
            $signed && $token instanceof UnencryptedToken,
            InvalidExtensionException::class,
            'This is not a signed install URL. Extensions can only be installed from a URL that Pterodactyl has signed.',
        );

        $claims = $token->claims();
        throw_unless(
            $token->hasBeenIssuedBy((string) config('extensions.signed_urls.issuer')) && $token->isPermittedFor((string) config('extensions.signed_urls.audience')),
            InvalidExtensionException::class,
            'This signed URL is not an extension install URL.',
        );
        // A token without an expiry never reports itself as expired, so one is required.
        throw_if(
            ! $claims->has('exp') || $token->isExpired(CarbonImmutable::now()),
            InvalidExtensionException::class,
            'This install URL has expired. Copy a new install command.',
        );

        $base = $claims->get('url');
        throw_unless(is_string($base) && $this->isUrl($base), InvalidExtensionException::class, 'This install URL does not say where to download the extension from.');

        return $base.$jwt;
    }

    /** Refuse a package that declares itself too large before any of it is written to disk. */
    private function assertWithinSizeLimit(ResponseInterface $response): void
    {
        throw_if(
            (int) $response->getHeaderLine('Content-Length') > self::MAX_PACKAGE_BYTES,
            InvalidExtensionException::class,
            'The extension package is larger than the panel accepts.',
        );
    }

    /** The server explains refusals in a JSON body, which was written to the sink file. */
    private function failureMessage(string $path, int $status): string
    {
        $body = rescue(fn (): mixed => json_decode(File::get($path), true), report: false);
        $message = is_array($body) ? ($body['error']['message'] ?? null) : null;

        return is_string($message)
            ? "The download was refused: {$message}"
            : "The download was refused (HTTP {$status}).";
    }
}
