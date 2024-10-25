<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Laminas\Diactoros\UriFactory as LaminasUriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;
use Slim\Psr7\Factory\UriFactory as SlimUriFactory;

use function class_exists;
use function count;
use function current;
use function explode;
use function function_exists;
use function getallheaders;
use function in_array;
use function is_numeric;
use function is_string;
use function parse_url;
use function preg_match;
use function strpos;
use function strstr;
use function substr;

use const PHP_URL_QUERY;

class ServerRequestFactory
{
    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var UriFactoryInterface */
    private $uriFactory;

    public function __construct(
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?UriFactoryInterface $uriFactory = null
    ) {
        $this->serverRequestFactory = $serverRequestFactory ?? $this->createServerRequestFactory();
        $this->uriFactory = $uriFactory ?? $this->createUriFactory();
    }

    private function createServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (class_exists(Psr17Factory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new Psr17Factory();
        } elseif (class_exists(HttpFactory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new HttpFactory();
        } elseif (class_exists(LaminasServerRequestFactory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new LaminasServerRequestFactory();
        } else {
            $factory = new SlimServerRequestFactory();
        }

        return $factory;
    }

    private function createUriFactory(): UriFactoryInterface
    {
        if (class_exists(Psr17Factory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new Psr17Factory();
        } elseif (class_exists(HttpFactory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new HttpFactory();
        } elseif (class_exists(LaminasUriFactory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new LaminasUriFactory();
        } else {
            $factory = new SlimUriFactory();
        }

        return $factory;
    }

    public static function createFromGlobals(): ServerRequest
    {
        if (class_exists(SlimServerRequestFactory::class)) {
            /** @psalm-suppress InternalMethod */
            $serverRequest = SlimServerRequestFactory::createFromGlobals();
        } elseif (class_exists(LaminasServerRequestFactory::class)) {
            /** @var ServerRequestInterface $serverRequest */
            $serverRequest = LaminasServerRequestFactory::fromGlobals();
        } else {
            $creator = new self();
            $serverRequest = self::createServerRequestFromGlobals($creator);
        }

        return new ServerRequest($serverRequest);
    }

    /**
     * @return array<string, string>
     */
    protected function getallheaders(): array
    {
        /** @var array<string, string> $headers */
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return $headers;
    }

    private static function createServerRequestFromGlobals(self $creator): ServerRequestInterface
    {
        $serverRequest = $creator->serverRequestFactory->createServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $creator->createUriFromGlobals($_SERVER),
            $_SERVER
        );

        foreach ($creator->getallheaders() as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }

        $serverRequest = $serverRequest->withQueryParams($_GET);

        if ($serverRequest->getMethod() !== 'POST') {
            return $serverRequest;
        }

        $contentType = '';
        foreach ($serverRequest->getHeader('Content-Type') as $headerValue) {
            $contentType = current(explode(';', $headerValue));
        }

        if (in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
            return $serverRequest->withParsedBody($_POST);
        }

        return $serverRequest;
    }

    /**
     * Create new Uri from environment.
     *
     * Initially based on the \Slim\Psr7\Factory\UriFactory::createFromGlobals() implementation.
     *
     * @param mixed[] $server
     */
    private function createUriFromGlobals(array $server): UriInterface
    {
        $uri = $this->uriFactory->createUri('');

        $uri = $uri->withScheme(! isset($server['HTTPS']) || $server['HTTPS'] === 'off' ? 'http' : 'https');

        if (isset($server['PHP_AUTH_USER']) && is_string($server['PHP_AUTH_USER']) && $server['PHP_AUTH_USER'] !== '') {
            $uri = $uri->withUserInfo(
                $server['PHP_AUTH_USER'],
                isset($server['PHP_AUTH_PW']) && is_string($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : null
            );
        }

        if (isset($server['HTTP_HOST']) && is_string($server['HTTP_HOST'])) {
            $uri = $uri->withHost($server['HTTP_HOST']);
        } elseif (isset($server['SERVER_NAME']) && is_string($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['SERVER_PORT']) && is_numeric($server['SERVER_PORT']) && $server['SERVER_PORT'] >= 1) {
            $uri = $uri->withPort((int) $server['SERVER_PORT']);
        } else {
            $uri = $uri->withPort($uri->getScheme() === 'https' ? 443 : 80);
        }

        if (preg_match('/^(\[[a-fA-F0-9:.]+])(:\d+)?\z/', $uri->getHost(), $matches)) {
            $uri = $uri->withHost($matches[1]);
            if (isset($matches[2])) {
                $uri = $uri->withPort((int) substr($matches[2], 1));
            }
        } else {
            $pos = strpos($uri->getHost(), ':');
            if ($pos !== false) {
                $port = (int) substr($uri->getHost(), $pos + 1);
                $host = (string) strstr($uri->getHost(), ':', true);
                $uri = $uri->withHost($host)->withPort($port);
            }
        }

        if (isset($server['QUERY_STRING']) && is_string($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        if (isset($server['REQUEST_URI']) && is_string($server['REQUEST_URI'])) {
            $uriFragments = explode('?', $server['REQUEST_URI']);
            $uri = $uri->withPath($uriFragments[0]);
            if ($uri->getQuery() === '' && count($uriFragments) > 1) {
                $query = parse_url('https://www.example.com' . $server['REQUEST_URI'], PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    $uri = $uri->withQuery($query);
                }
            }
        }

        return $uri;
    }
}
