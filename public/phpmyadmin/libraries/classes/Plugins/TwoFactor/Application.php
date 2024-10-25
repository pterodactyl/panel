<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\TwoFactor;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;

use function __;
use function extension_loaded;

/**
 * HOTP and TOTP based two-factor authentication
 *
 * Also known as Google, Authy, or OTP
 */
class Application extends TwoFactorPlugin
{
    /** @var string */
    public static $id = 'application';

    /** @var Google2FA */
    protected $google2fa;

    /**
     * Creates object
     *
     * @param TwoFactor $twofactor TwoFactor instance
     */
    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);
        $this->google2fa = new Google2FA();
        $this->google2fa->setWindow(8);
        if (isset($this->twofactor->config['settings']['secret'])) {
            return;
        }

        $this->twofactor->config['settings']['secret'] = '';
    }

    public function getGoogle2fa(): Google2FA
    {
        return $this->google2fa;
    }

    /**
     * Checks authentication, returns true on success
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function check(): bool
    {
        $this->provided = false;
        if (! isset($_POST['2fa_code'])) {
            return false;
        }

        $this->provided = true;

        return (bool) $this->google2fa->verifyKey($this->twofactor->config['settings']['secret'], $_POST['2fa_code']);
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return $this->template->render('login/twofactor/application');
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup()
    {
        $secret = $this->twofactor->config['settings']['secret'];
        $inlineUrl = $this->google2fa->getQRCodeInline(
            'phpMyAdmin (' . $this->getAppId(false) . ')',
            $this->twofactor->user,
            $secret
        );

        return $this->template->render('login/twofactor/application_configure', [
            'image' => $inlineUrl,
            'secret' => $secret,
            'has_imagick' => extension_loaded('imagick'),
        ]);
    }

    /**
     * Performs backend configuration
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function configure(): bool
    {
        if (! isset($_SESSION['2fa_application_key'])) {
            $_SESSION['2fa_application_key'] = $this->google2fa->generateSecretKey();
        }

        $this->twofactor->config['settings']['secret'] = $_SESSION['2fa_application_key'];

        $result = $this->check();
        if ($result) {
            unset($_SESSION['2fa_application_key']);
        }

        return $result;
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Authentication Application (2FA)');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __(
            'Provides authentication using HOTP and TOTP applications such as FreeOTP, Google Authenticator or Authy.'
        );
    }
}
