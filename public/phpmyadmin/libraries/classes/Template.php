<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Twig\AssetExtension;
use PhpMyAdmin\Twig\CoreExtension;
use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use PhpMyAdmin\Twig\FlashMessagesExtension;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\MessageExtension;
use PhpMyAdmin\Twig\RelationExtension;
use PhpMyAdmin\Twig\SanitizeExtension;
use PhpMyAdmin\Twig\TableExtension;
use PhpMyAdmin\Twig\TrackerExtension;
use PhpMyAdmin\Twig\TransformationsExtension;
use PhpMyAdmin\Twig\UrlExtension;
use PhpMyAdmin\Twig\UtilExtension;
use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;
use Twig\TemplateWrapper;

use function __;
use function is_array;
use function sprintf;
use function trigger_error;

use const E_USER_WARNING;

/**
 * Handle front end templating
 */
class Template
{
    /**
     * Twig environment
     *
     * @var Environment
     */
    protected static $twig;

    public const TEMPLATES_FOLDER = ROOT_PATH . 'templates';

    public function __construct()
    {
        if (static::$twig !== null) {
            return;
        }

        /** @var Config|null $config */
        $config = $GLOBALS['config'];
        $cacheDir = $config !== null ? $config->getTempDir('twig') : null;

        static::$twig = self::getTwigEnvironment($cacheDir);
    }

    public static function getTwigEnvironment(?string $cacheDir): Environment
    {
        global $cfg, $containerBuilder;

        /* Twig expects false when cache is not configured */
        if ($cacheDir === null) {
            $cacheDir = false;
        }

        $loader = new FilesystemLoader(self::TEMPLATES_FOLDER);
        $twig = new Environment($loader, [
            'auto_reload' => true,
            'cache' => $cacheDir,
        ]);

        $twig->addRuntimeLoader(new ContainerRuntimeLoader($containerBuilder));

        if (is_array($cfg) && ($cfg['environment'] ?? '') === 'development') {
            $twig->enableDebug();
            $twig->addExtension(new DebugExtension());
            // This will enable debug for the extension to print lines
            // It is used in po file lines re-mapping
            TransNode::$enableAddDebugInfo = true;
        }

        if ($cfg['environment'] === 'production') {
            $twig->disableDebug();
            TransNode::$enableAddDebugInfo = false;
        }

        $twig->addExtension(new AssetExtension());
        $twig->addExtension(new CoreExtension());
        $twig->addExtension(new FlashMessagesExtension());
        $twig->addExtension(new I18nExtension());
        $twig->addExtension(new MessageExtension());
        $twig->addExtension(new RelationExtension());
        $twig->addExtension(new SanitizeExtension());
        $twig->addExtension(new TableExtension());
        $twig->addExtension(new TrackerExtension());
        $twig->addExtension(new TransformationsExtension());
        $twig->addExtension(new UrlExtension());
        $twig->addExtension(new UtilExtension());

        return $twig;
    }

    /**
     * Loads a template.
     *
     * @param string $templateName Template path name
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function load(string $templateName): TemplateWrapper
    {
        try {
            $template = static::$twig->load($templateName . '.twig');
        } catch (RuntimeException $e) {
            /* Retry with disabled cache */
            static::$twig->setCache(false);
            $template = static::$twig->load($templateName . '.twig');
            /*
             * The trigger error is intentionally after second load
             * to avoid triggering error when disabling cache does not
             * solve it.
             */
            trigger_error(
                sprintf(
                    __('Error while working with template cache: %s'),
                    $e->getMessage()
                ),
                E_USER_WARNING
            );
        }

        return $template;
    }

    /**
     * @param string $template Template path name
     * @param array  $data     Associative array of template variables
     *
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, array $data = []): string
    {
        return $this->load($template)->render($data);
    }
}
