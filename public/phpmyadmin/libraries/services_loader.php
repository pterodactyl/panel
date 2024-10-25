<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Reference;

use function is_string;
use function substr;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $loadServices = static function (array $servicesFile, ServicesConfigurator $services): void {
        foreach ($servicesFile['services'] as $serviceName => $service) {
            if (is_string($service)) {
                $services->alias($serviceName, $service);
                continue;
            }

            $theService = $services->set($serviceName, $service['class'] ?? null);
            if (isset($service['arguments'])) {// !== null check
                foreach ($service['arguments'] as &$argumentName) {
                    if ($argumentName[0] !== '@') {
                        continue;
                    }

                    $services->alias($serviceName, substr($argumentName, 1));
                    $argumentName = new Reference(substr($argumentName, 1));
                }

                $theService->args($service['arguments']);
            }

            if (! isset($service['factory'])) {
                continue;
            }

            // !== null check
            $theService->factory($service['factory']);
        }
    };

    $servicesFile = include ROOT_PATH . 'libraries/services.php';
    $loadServices($servicesFile, $services);
    $servicesFile = include ROOT_PATH . 'libraries/services_controllers.php';
    $loadServices($servicesFile, $services);
};
