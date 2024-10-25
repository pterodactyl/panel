<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;

/**
 * Invalid two-factor authentication showing that configured choice is not available.
 */
class Invalid extends TwoFactorPlugin
{
    /** @var string */
    public static $id = 'invalid';

    /** @var bool */
    public static $showSubmit = false;

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        return false;
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return $this->template->render('login/twofactor/invalid');
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return 'Invalid two-factor authentication';
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return 'Error fallback only!';
    }
}
