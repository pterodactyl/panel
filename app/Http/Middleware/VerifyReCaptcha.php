<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyReCaptcha
{
    /**
     * VerifyReCaptcha constructor.
     */
    public function __construct(private readonly Dispatcher $dispatcher, private readonly Repository $config) {}

    /**
     * Handle an incoming request.
     */
    /**
     * @param  Closure(Request): \Symfony\Component\HttpFoundation\Response  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->config->get('recaptcha.enabled')) {
            return $next($request);
        }

        $result = ['success' => false, 'hostname' => null];

        if ($request->filled('g-recaptcha-response')) {
            try {
                $client = new Client(['timeout' => 5, 'connect_timeout' => 2]);
                $res = $client->post(JsonValueGuard::string($this->config->get('recaptcha.domain')), [
                    'form_params' => [
                        'secret' => $this->config->get('recaptcha.secret_key'),
                        'response' => $request->input('g-recaptcha-response'),
                    ],
                ]);

                if ($res->getStatusCode() === 200) {
                    $result = $this->decodeResponse($res->getBody()->__toString());

                    if ($result['success'] && (! $this->config->get('recaptcha.verify_domain') || $this->isResponseVerified($result, $request))) {
                        return $next($request);
                    }
                }
            } catch (GuzzleException $exception) {
                Log::warning('Failed to communicate with reCAPTCHA verification service', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->dispatcher->dispatch(
            new FailedCaptcha(
                $request->ip() ?? 'unknown',
                $result['hostname'] ?? 'unknown'
            )
        );

        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate reCAPTCHA data.');
    }

    /**
     * Determine if the response from the recaptcha servers was valid.
     */
    /**
     * @param  array{success: bool, hostname: string|null}  $result
     */
    private function isResponseVerified(array $result, Request $request): bool
    {
        $url = parse_url($request->url());

        return $url !== false && $result['hostname'] === Arr::get($url, 'host');
    }

    /**
     * @return array{success: bool, hostname: string|null}
     */
    private function decodeResponse(string $response): array
    {
        $decoded = json_decode($response, true);
        if (! is_array($decoded)) {
            return ['success' => false, 'hostname' => null];
        }

        $success = $decoded['success'] ?? false;
        $hostname = $decoded['hostname'] ?? null;

        return [
            'success' => is_bool($success) && $success,
            'hostname' => is_string($hostname) ? $hostname : null,
        ];
    }
}
