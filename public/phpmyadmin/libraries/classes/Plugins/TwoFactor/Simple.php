<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;

use function __;

/**
 * Simple two-factor authentication auth asking just for confirmation.
 *
 * This has no practical use, but can be used for testing.
 */
class Simple extends TwoFactorPlugin
{
    /** @var string */
    public static $id = 'simple';

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        return isset($_POST['2fa_confirm']);
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return $this->template->render('login/twofactor/simple');
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Simple two-factor authentication');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('For testing purposes only!');
    }
}
