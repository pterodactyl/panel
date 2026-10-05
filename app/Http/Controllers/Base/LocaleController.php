<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Http\JsonResponse;
use Illuminate\Translation\Translator;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Base\LocaleRequest;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

class LocaleController extends Controller
{
    protected Loader $loader;

    public function __construct(Translator $translator)
    {
        $this->loader = $translator->getLoader();
    }

    /**
     * Returns translation data given a specific locale and namespace.
     */
    public function __invoke(LocaleRequest $request): JsonResponse
    {
        $locale = JsonValueGuard::string($request->validated('locale'));
        $namespace = JsonValueGuard::string($request->validated('namespace'));
        $segments = explode('::', $namespace, 2);
        $translations = count($segments) === 2
            ? $this->loader->load($locale, $segments[1], $segments[0])
            : $this->loader->load($locale, $namespace);
        JsonValueGuard::assertValue($translations);
        $response[$locale][$namespace] = $this->i18n($translations);

        return new JsonResponse($response, 200, [
            // Cache this in the browser for an hour, and allow the browser to use a stale
            // cache for up to a day after it was created while it fetches an updated set
            // of translation keys.
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'ETag' => hash('sha256', json_encode($response, JSON_THROW_ON_ERROR)),
        ]);
    }

    /**
     * Convert standard Laravel translation keys that look like ":foo"
     * into key structures that are supported by the front-end i18n
     * library, like "{{foo}}".
     *
     * @param  array<array-key, JsonInputValue>  $data
     * @return array<array-key, JsonInputValue>
     */
    protected function i18n(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->i18n($value);
            } else {
                throw_unless(is_string($value), UnexpectedValueException::class, 'Translation leaves must be strings.');
                // Rewrite any Laravel-style ":name" placeholders into the "{{name}}" form the
                // front-end i18n library understands.
                $data[$key] = preg_replace('/:([\w.-]+\w)([^\w:]?|$)/m', '{{$1}}$2', $value);
            }
        }

        return $data;
    }
}
