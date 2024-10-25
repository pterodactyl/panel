<?php
/**
 * Two authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;

use function __;
use function is_array;
use function parse_url;
use function sprintf;
use function strlen;

/**
 * Two factor authentication plugin class
 *
 * This is basic implementation which does no
 * additional authentication, subclasses are expected
 * to implement this.
 */
class TwoFactorPlugin
{
    /** @var string */
    public static $id = '';

    /**
     * Whether to show submit button in form
     *
     * @var bool
     */
    public static $showSubmit = true;

    /** @var TwoFactor */
    protected $twofactor;

    /** @var bool */
    protected $provided = false;

    /** @var string */
    protected $message = '';

    /** @var Template */
    public $template;

    /**
     * Creates object
     *
     * @param TwoFactor $twofactor TwoFactor instance
     */
    public function __construct(TwoFactor $twofactor)
    {
        $this->twofactor = $twofactor;
        $this->template = new Template();
    }

    /**
     * Returns authentication error message
     *
     * @return string
     */
    public function getError()
    {
        if ($this->provided) {
            if (! empty($this->message)) {
                return Message::rawError(
                    sprintf(__('Two-factor authentication failed: %s'), $this->message)
                )->getDisplay();
            }

            return Message::rawError(
                __('Two-factor authentication failed.')
            )->getDisplay();
        }

        return '';
    }

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        return true;
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return '';
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup()
    {
        return '';
    }

    /**
     * Performs backend configuration
     */
    public function configure(): bool
    {
        return true;
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('No Two-Factor Authentication');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('Login using password only.');
    }

    /**
     * Return an application ID
     *
     * Either hostname or hostname with scheme.
     *
     * @param bool $return_url Whether to generate URL
     *
     * @return string
     */
    public function getAppId($return_url)
    {
        global $config;

        $url = $config->get('PmaAbsoluteUri');
        $parsed = [];
        if (! empty($url)) {
            $parsedUrl = parse_url($url);

            if (is_array($parsedUrl)) {
                $parsed = $parsedUrl;
            }
        }

        if (! isset($parsed['scheme']) || strlen($parsed['scheme']) === 0) {
            $parsed['scheme'] = $config->isHttps() ? 'https' : 'http';
        }

        if (! isset($parsed['host']) || strlen($parsed['host']) === 0) {
            $parsed['host'] = Core::getenv('HTTP_HOST');
        }

        if ($return_url) {
            $port = '';
            if (isset($parsed['port'])) {
                $port = ':' . $parsed['port'];
            }

            return sprintf('%s://%s%s', $parsed['scheme'], $parsed['host'], $port);
        }

        return $parsed['host'];
    }
}
