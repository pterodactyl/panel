<?php

namespace Pterodactyl\Classes\PayPal\sdk\Handler;

/**
 * Interface IPayPalHandler
 *
 * @package PayPal\Handler
 */
interface IPayPalHandler
{
    /**
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Core\PayPalHttpConfig $httpConfig
     * @param string $request
     * @param mixed $options
     * @return mixed
     */
    public function handle($httpConfig, $request, $options);
}
