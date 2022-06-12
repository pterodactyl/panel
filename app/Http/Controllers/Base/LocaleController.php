<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Translation\Translator;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Translation\Loader;

class LocaleController extends Controller
{
    protected Loader $loader;

    public function __construct(Translator $translator)
    {
        $this->loader = $translator->getLoader();
    }

    /**
     * Returns translation data given a specific locale and namespace.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $locales = explode(' ', $request->input('locale') ?? '');
        $namespaces = explode(' ', $request->input('namespace') ?? '');

        $response = [];
        foreach ($locales as $locale) {
            $response[$locale] = [];
            foreach ($namespaces as $namespace) {
                $response[$locale][$namespace] = $this->i18n(
                    $this->loader->load($locale, str_replace('.', '/', $namespace))
                );
            }
        }

        return new JsonResponse($response, 200, [
            // Cache this in the browser for an hour, and allow the browser to use a stale
            // cache for up to a day after it was created while it fetches an updated set
            // of translation keys.
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'ETag' => md5(json_encode($response, JSON_THROW_ON_ERROR)),
        ]);
    }

    /**
     * Convert standard Laravel translation keys that look like ":foo"
     * into key structures that are supported by the front-end i18n
     * library, like "{{foo}}".
     */
    protected function i18n(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->i18n($value);
            } else {
                $data[$key] = preg_replace('/:([\w-]+)(\W?|$)/m', '{{$1}}$2', $value);
            }
        }

        return $data;
    }
}
