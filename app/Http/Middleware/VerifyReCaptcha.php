<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyReCaptcha
{
    /**
     * Handle an incoming request.
     */
    /**
     * @param  Closure(Request): \Symfony\Component\HttpFoundation\Response  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! config('recaptcha.enabled')) {
            return $next($request);
        }

        $result = $this->verifyCaptcha($request);

        $domainValid = ! config('recaptcha.verify_domain') || $this->isResponseVerified($result, $request);

        if ($result['success'] && $domainValid) {
            return $next($request);
        }

        event(
            new FailedCaptcha(
                $request->ip() ?? 'unknown',
                $result['hostname'] ?? 'unknown'
            )
        );

        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate reCAPTCHA data.');
    }

    /**
     * @return array{success: bool, hostname: string|null}
     */
    private function verifyCaptcha(Request $request): array
    {
        if (! $request->filled('g-recaptcha-response')) {
            return ['success' => false, 'hostname' => null];
        }

        try {
            $client = new Client(['timeout' => 5, 'connect_timeout' => 2]);
            $res = $client->post(JsonValueGuard::string(config('recaptcha.domain')), [
                'form_params' => [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $request->input('g-recaptcha-response'),
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                return $this->decodeResponse($res->getBody()->__toString());
            }
        } catch (GuzzleException) {
            // Ignore the error entirely, we will just return a failed response below.
        }

        return ['success' => false, 'hostname' => null];
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
