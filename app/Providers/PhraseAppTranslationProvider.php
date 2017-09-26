<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Providers;

use Pterodactyl\Extensions\PhraseAppTranslator;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Translation\Translator as IlluminateTranslator;

class PhraseAppTranslationProvider extends TranslationServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            if ($app['config']['pterodactyl.lang.in_context']) {
                $trans = new PhraseAppTranslator($loader, $locale);
            } else {
                $trans = new IlluminateTranslator($loader, $locale);
            }

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }
}
