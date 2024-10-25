<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\MoTranslator\Loader;

use function class_exists;

/**
 * Defines the localization helper infrastructure of the library.
 */
class Translator
{
    /**
     * The MoTranslator loader object.
     *
     * @var Loader
     */
    private static $loader;

    /**
     * The MoTranslator translator object.
     *
     * @var \PhpMyAdmin\MoTranslator\Translator
     */
    private static $translator;

    /**
     * Loads translator.
     *
     * @return void
     */
    public static function load()
    {
        if (self::$loader === null) {
            // Create loader object
            self::$loader = new Loader();

            // Set locale
            self::$loader->setlocale(
                self::$loader->detectlocale()
            );

            // Set default text domain
            self::$loader->textdomain('sqlparser');

            // Set path where to look for a domain
            self::$loader->bindtextdomain('sqlparser', __DIR__ . '/../locale/');
        }

        if (self::$translator !== null) {
            return;
        }

        // Get translator
        self::$translator = self::$loader->getTranslator();
    }

    /**
     * Translates a string.
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    public static function gettext($msgid)
    {
        if (! class_exists('\PhpMyAdmin\MoTranslator\Loader', true)) {
            return $msgid;
        }

        self::load();

        return self::$translator->gettext($msgid);
    }
}
