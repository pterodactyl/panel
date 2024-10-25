<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Container\ContainerInterface;

use function __;
use function file_exists;
use function file_put_contents;
use function htmlspecialchars;
use function is_array;
use function is_readable;
use function is_string;
use function is_writable;
use function rawurldecode;
use function sprintf;
use function trigger_error;
use function var_export;

use const CACHE_DIR;
use const E_USER_WARNING;
use const ROOT_PATH;

/**
 * Class used to warm up the routing cache and manage routing.
 */
class Routing
{
    public const ROUTES_CACHE_FILE = CACHE_DIR . 'routes.cache.php';

    public static function getDispatcher(): Dispatcher
    {
        $routes = require ROOT_PATH . 'libraries/routes.php';

        return self::routesCachedDispatcher($routes);
    }

    public static function skipCache(): bool
    {
        global $cfg;

        return ($cfg['environment'] ?? '') === 'development';
    }

    public static function canWriteCache(): bool
    {
        $cacheFileExists = file_exists(self::ROUTES_CACHE_FILE);
        $canWriteFile = is_writable(self::ROUTES_CACHE_FILE);
        if ($cacheFileExists && $canWriteFile) {
            return true;
        }

        // Write without read does not work, chmod 200 for example
        if (! $cacheFileExists && is_writable(CACHE_DIR) && is_readable(CACHE_DIR)) {
            return true;
        }

        return $canWriteFile;
    }

    private static function routesCachedDispatcher(callable $routeDefinitionCallback): Dispatcher
    {
        $skipCache = self::skipCache();

        // If skip cache is enabled, do not try to read the file
        // If no cache skipping then read it and use it
        if (! $skipCache && file_exists(self::ROUTES_CACHE_FILE)) {
            /** @psalm-suppress MissingFile, UnresolvableInclude, MixedAssignment */
            $dispatchData = require self::ROUTES_CACHE_FILE;
            if (self::isRoutesCacheFileValid($dispatchData)) {
                return new DispatcherGroupCountBased($dispatchData);
            }
        }

        $routeCollector = new RouteCollector(
            new RouteParserStd(),
            new DataGeneratorGroupCountBased()
        );
        $routeDefinitionCallback($routeCollector);

        $dispatchData = $routeCollector->getData();
        $canWriteCache = self::canWriteCache();

        // If skip cache is enabled, do not try to write it
        // If no skip cache then try to write if write is possible
        if (! $skipCache && $canWriteCache) {
            $writeWorks = self::writeCache(
                '<?php return ' . var_export($dispatchData, true) . ';'
            );
            if (! $writeWorks) {
                trigger_error(
                    sprintf(
                        __(
                            'The routing cache could not be written, '
                            . 'you need to adjust permissions on the folder/file "%s"'
                        ),
                        self::ROUTES_CACHE_FILE
                    ),
                    E_USER_WARNING
                );
            }
        }

        return new DispatcherGroupCountBased($dispatchData);
    }

    public static function writeCache(string $cacheContents): bool
    {
        return @file_put_contents(self::ROUTES_CACHE_FILE, $cacheContents) !== false;
    }

    /**
     * @psalm-return non-empty-string
     */
    public static function getCurrentRoute(): string
    {
        /** @var mixed $route */
        $route = $_GET['route'] ?? $_POST['route'] ?? '/';
        if (! is_string($route) || $route === '') {
            $route = '/';
        }

        /**
         * See FAQ 1.34.
         *
         * @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34
         */
        $db = isset($_GET['db']) && is_string($_GET['db']) ? $_GET['db'] : '';
        if ($route === '/' && $db !== '') {
            $table = isset($_GET['table']) && is_string($_GET['table']) ? $_GET['table'] : '';
            $route = $table === '' ? '/database/structure' : '/sql';
        }

        return $route;
    }

    /**
     * Call associated controller for a route using the dispatcher
     */
    public static function callControllerForRoute(
        ServerRequest $request,
        string $route,
        Dispatcher $dispatcher,
        ContainerInterface $container
    ): void {
        $routeInfo = $dispatcher->dispatch($request->getMethod(), rawurldecode($route));

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            /** @var ResponseRenderer $response */
            $response = $container->get(ResponseRenderer::class);
            $response->setHttpResponseCode(404);
            echo Message::error(sprintf(
                __('Error 404! The page %s was not found.'),
                '<code>' . htmlspecialchars($route) . '</code>'
            ))->getDisplay();

            return;
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var ResponseRenderer $response */
            $response = $container->get(ResponseRenderer::class);
            $response->setHttpResponseCode(405);
            echo Message::error(__('Error 405! Request method not allowed.'))->getDisplay();

            return;
        }

        if ($routeInfo[0] !== Dispatcher::FOUND) {
            return;
        }

        /** @psalm-var class-string $controllerName */
        $controllerName = $routeInfo[1];
        /** @var array<string, string> $vars */
        $vars = $routeInfo[2];

        /**
         * @psalm-var callable(ServerRequest=, array<string, string>=):void $controller
         */
        $controller = $container->get($controllerName);
        $controller($request, $vars);
    }

    /**
     * @param mixed $dispatchData
     *
     * @psalm-assert-if-true array[] $dispatchData
     */
    private static function isRoutesCacheFileValid($dispatchData): bool
    {
        return is_array($dispatchData)
            && isset($dispatchData[0], $dispatchData[1])
            && is_array($dispatchData[0]) && is_array($dispatchData[1])
            && isset($dispatchData[0]['GET']) && is_array($dispatchData[0]['GET'])
            && isset($dispatchData[0]['GET']['/']) && is_string($dispatchData[0]['GET']['/'])
            && $dispatchData[0]['GET']['/'] === HomeController::class;
    }
}
