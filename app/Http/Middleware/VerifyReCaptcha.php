<?php

namespace Pterodactyl\Http\Middleware;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyReCaptcha
{
    /**
     * VerifyReCaptcha constructor.
     */
    public function __construct(private Dispatcher $dispatcher, private Repository $config)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!$this->config->get('recaptcha.enabled')) {
            return $next($request);
        }

        if ($request->filled('g-recaptcha-response')) {
            $client = new Client();
            $res = $client->post($this->config->get('recaptcha.domain'), [
                'form_params' => [
                    'secret' => $this->config->get('recaptcha.secret_key'),
                    'response' => $request->input('g-recaptcha-response'),
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());

                if ($result->success && (!$this->config->get('recaptcha.verify_domain') || $this->isResponseVerified($result, $request))) {
                    return $next($request);
                }
            }
        }

        $this->dispatcher->dispatch(
            new FailedCaptcha(
                $request->ip(),
                !empty($result) ? ($result->hostname ?? null) : null
            )
        );

        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate reCAPTCHA data.');
    }

    /**
     * Determine if the response from the recaptcha servers was valid.
     */
    private function isResponseVerified(\stdClass $result, Request $request): bool
    {
        if (!$this->config->get('recaptcha.verify_domain')) {
            return false;
        }

        $url = parse_url($request->url());

        return $result->hostname === array_get($url, 'host');
    }
}
