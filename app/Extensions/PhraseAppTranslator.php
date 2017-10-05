<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Extensions;

use Illuminate\Translation\Translator as LaravelTranslator;

class PhraseAppTranslator extends LaravelTranslator
{
    /**
     * Get the translation for the given key.
     *
     * @param string      $key
     * @param array       $replace
     * @param string|null $locale
     * @param bool        $fallback
     * @return string
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $key = substr($key, strpos($key, '.') + 1);

        return "{{__phrase_${key}__}}";
    }
}
