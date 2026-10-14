<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Daemon;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Node;
use UnexpectedValueException;

/** @phpstan-type RequestOptions array{json?: array<string, mixed>, query?: array<string, int|string>, body?: string, timeout?: int} */
final readonly class DaemonConnection
{
    private function __construct(private string $url, private string $token) {}

    public static function forNode(Node $node): self
    {
        return new self($node->getConnectionAddress(), $node->getDecryptedKey());
    }

    /** @param RequestOptions $options */
    public function request(string $method, string $path, array $options = [], bool $useStatusCode = true): Response
    {
        $request = Http::baseUrl($this->url)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(config('pterodactyl.guzzle.timeout'))
            ->connectTimeout(config('pterodactyl.guzzle.connect_timeout'))
            ->withOptions(['verify' => App::isProduction()]);

        if (isset($options['body'])) {
            $request->withBody($options['body'], 'application/json');
            unset($options['body']);
        }

        try {
            return $request->send($method, $path, $options)->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new DaemonConnectionException($exception, $useStatusCode);
        }
    }

    /**
     * @param  NormalizedValidationRules  $rules
     * @return array<mixed>
     */
    public function decode(Response $response, array $rules = [], bool $expectsList = false): array
    {
        try {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            throw_unless(is_array($data), UnexpectedValueException::class, 'Wings did not return a JSON object or list.');
            throw_if(! $expectsList && array_any(array_keys($data), fn (int|string $key): bool => is_int($key)), UnexpectedValueException::class, 'Wings returned a list where a JSON object was expected.');

            Validator::make(['data' => $data], $rules)->validate();

            return $data;
        } catch (JsonException|UnexpectedValueException|ValidationException $exception) {
            throw new DaemonConnectionException($exception, response: $response);
        }
    }
}
