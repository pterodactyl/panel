<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Pterodactyl\Events\Auth\FailedCaptcha;

class VerifyReCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\RediectResponse
     */
    public function handle($request, Closure $next)
    {
        if (! config('recaptcha.enabled')) {
            return $next($request);
        }

        if ($request->has('g-recaptcha-response')) {
            $client = new \GuzzleHttp\Client();
            $res = $client->post(config('recaptcha.domain'), [
                'form_params' => [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $request->input('g-recaptcha-response'),
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());

                $verified = function ($result, $request) {
                    if (! config('recaptcha.verify_domain')) {
                        return false;
                    }

                    $url = parse_url($request->url());

                    if (array_key_exists('host', $url)) {
                        return $result->hostname === $url['host'];
                    }
                };

                if ($result->success && (! config('recaptcha.verify_domain') || $verified($result, $request))) {
                    return $next($request);
                }
            }
        }

        // Emit an event and return to the previous view with an error (only the captcha error will be shown!)
        event(new FailedCaptcha($request->ip(), (! isset($result->hostname) ?: $result->hostname)));

        return back()->withErrors(['g-recaptcha-response' => trans('strings.captcha_invalid')])->withInput();
    }
}
