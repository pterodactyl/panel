<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Base;

use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Services\Extensions\ExtensionManifest;

class LocaleRequest extends FormRequest
{
    private const string LOCALE = ExtensionManifest::LOCALE_PATTERN;

    private const string NAMESPACE = '(?:[a-z]{1,191}|ext-[a-z][a-z0-9-]{0,47}::[a-z][a-z0-9_-]{0,63})';

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'regex:/^'.self::LOCALE.'(?:[+ ]'.self::LOCALE.'){0,7}$/D'],
            'namespace' => ['required', 'string', 'regex:/^'.self::NAMESPACE.'(?:[+ ]'.self::NAMESPACE.'){0,31}$/D'],
            // The revision of an extension's translations, which only makes the URL unique.
            'revision' => ['sometimes', 'string', 'regex:/^[a-f0-9]{32}$/D'],
        ];
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return $this->listed('locale');
    }

    /**
     * @return list<string>
     */
    public function namespaces(): array
    {
        return $this->listed('namespace');
    }

    /**
     * @return list<string>
     */
    private function listed(string $key): array
    {
        return array_values(array_unique(preg_split('/[+ ]/', $this->string($key)->toString(), flags: PREG_SPLIT_NO_EMPTY) ?: []));
    }
}
