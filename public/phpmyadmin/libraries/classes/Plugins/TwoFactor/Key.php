<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use CodeLts\U2F\U2FServer\U2FException;
use CodeLts\U2F\U2FServer\U2FServer;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\TwoFactor;
use stdClass;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function __;
use function is_array;
use function is_object;
use function json_decode;
use function json_encode;

/**
 * Hardware key based two-factor authentication
 *
 * Supports FIDO U2F tokens
 */
class Key extends TwoFactorPlugin
{
    /** @var string */
    public static $id = 'key';

    /**
     * Creates object
     *
     * @param TwoFactor $twofactor TwoFactor instance
     */
    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);
        if (
            isset($this->twofactor->config['settings']['registrations'])
            && is_array($this->twofactor->config['settings']['registrations'])
        ) {
            return;
        }

        $this->twofactor->config['settings']['registrations'] = [];
    }

    /**
     * Returns array of U2F registration objects
     *
     * @return stdClass[]
     */
    public function getRegistrations()
    {
        $result = [];
        foreach ($this->twofactor->config['settings']['registrations'] as $index => $data) {
            $reg = new stdClass();
            $reg->keyHandle = $data['keyHandle'];
            $reg->publicKey = $data['publicKey'];
            $reg->certificate = $data['certificate'];
            $reg->counter = $data['counter'];
            $reg->index = $index;
            $result[] = $reg;
        }

        return $result;
    }

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        $this->provided = false;
        if (! isset($_POST['u2f_authentication_response'], $_SESSION['authenticationRequest'])) {
            return false;
        }

        $this->provided = true;
        try {
            $response = json_decode($_POST['u2f_authentication_response']);
            if (! is_object($response)) {
                return false;
            }

            $auth = U2FServer::authenticate(
                $_SESSION['authenticationRequest'],
                $this->getRegistrations(),
                $response
            );
            $this->twofactor->config['settings']['registrations'][$auth->index]['counter'] = $auth->counter;
            $this->twofactor->save();

            return true;
        } catch (U2FException $e) {
            $this->message = $e->getMessage();

            return false;
        }
    }

    /**
     * Loads needed javascripts into the page
     */
    public function loadScripts(): void
    {
        $response = ResponseRenderer::getInstance();
        $scripts = $response->getHeader()->getScripts();
        $scripts->addFile('vendor/u2f-api-polyfill.js');
        $scripts->addFile('u2f.js');
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        $request = U2FServer::makeAuthentication(
            $this->getRegistrations(),
            $this->getAppId(true)
        );
        $_SESSION['authenticationRequest'] = $request;
        $this->loadScripts();

        return $this->template->render('login/twofactor/key', [
            'request' => json_encode($request),
            'is_https' => $GLOBALS['config']->isHttps(),
        ]);
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     *
     * @throws U2FException
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function setup()
    {
        $registrationData = U2FServer::makeRegistration(
            $this->getAppId(true),
            $this->getRegistrations()
        );
        $_SESSION['registrationRequest'] = $registrationData['request'];

        $this->loadScripts();

        return $this->template->render('login/twofactor/key_configure', [
            'request' => json_encode($registrationData['request']),
            'signatures' => json_encode($registrationData['signatures']),
            'is_https' => $GLOBALS['config']->isHttps(),
        ]);
    }

    /**
     * Performs backend configuration
     */
    public function configure(): bool
    {
        $this->provided = false;
        if (! isset($_POST['u2f_registration_response'], $_SESSION['registrationRequest'])) {
            return false;
        }

        $this->provided = true;
        try {
            $response = json_decode($_POST['u2f_registration_response']);
            if (! is_object($response)) {
                return false;
            }

            $registration = U2FServer::register($_SESSION['registrationRequest'], $response);
            $this->twofactor->config['settings']['registrations'][] = [
                'keyHandle' => $registration->getKeyHandle(),
                'publicKey' => $registration->getPublicKey(),
                'certificate' => $registration->getCertificate(),
                'counter' => $registration->getCounter(),
            ];

            return true;
        } catch (U2FException $e) {
            $this->message = $e->getMessage();

            return false;
        }
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Hardware Security Key (FIDO U2F)');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('Provides authentication using hardware security tokens supporting FIDO U2F, such as a YubiKey.');
    }
}
