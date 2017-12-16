<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use stdClass;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Illuminate\Contracts\Config\Repository;

class VerifyReCaptcha
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * VerifyReCaptcha constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->config->get('recaptcha.enabled')) {
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

                if ($result->success && (! $this->config->get('recaptcha.verify_domain') || $this->isResponseVerified($result, $request))) {
                    return $next($request);
                }
            }
        }

        // Emit an event and return to the previous view with an error (only the captcha error will be shown!)
        event(new FailedCaptcha($request->ip(), (! isset($result) ?: object_get($result, 'hostname'))));

        return redirect()->back()->withErrors(['g-recaptcha-response' => trans('strings.captcha_invalid')])->withInput();
    }

    /**
     * Determine if the response from the recaptcha servers was valid.
     *
     * @param stdClass                 $result
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function isResponseVerified(stdClass $result, Request $request): bool
    {
        if (! $this->config->get('recaptcha.verify_domain')) {
            return false;
        }

        $url = parse_url($request->url());

        return $result->hostname === array_get($url, 'host');
    }
}
