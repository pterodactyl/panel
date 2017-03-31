<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Alert;
use \Pterodactyl\Events\Auth\FailedCaptcha;

class VerifyReCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!config('recaptcha.enabled')) return $next($request);
        
        $response_domain = null;

        if ($request->has('g-recaptcha-response')) {
            $response = $request->get('g-recaptcha-response');

            $client = new \GuzzleHttp\Client();
            $res = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $response,
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());

                $response_domain = $result->hostname;

                // Compare the domain received by google with the app url
                $domain_verified = false;
                if (config('recaptcha.verify_domain')) {
                   $matches;
                   preg_match('/^(?:https?:\/\/)?((?:www\.)?[^:\/\n]+)/', config('app.url'), $matches);
                   $domain = $matches[1];
                   $domain_verified = $response_domain === $domain;
                }

                if ($result->success && (!config('recaptcha.verify_domain') || $domain_verified)) {
                    return $next($request);
                }
            }
        }
        
        // Emit an event and return to the previous view with an error (only the captcha error will be shown!)
        event(new FailedCaptcha($request->ip(), $response_domain));
        return back()->withErrors(['g-recaptcha-response' => trans('strings.captcha_invalid')])->withInput();
    }
}
