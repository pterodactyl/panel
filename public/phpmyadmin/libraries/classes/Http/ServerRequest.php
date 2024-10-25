<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use function is_array;
use function is_object;
use function property_exists;

class ServerRequest implements ServerRequestInterface
{
    /** @var ServerRequestInterface */
    private $serverRequest;

    final public function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->serverRequest->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        $serverRequest = $this->serverRequest->withProtocolVersion($version);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->serverRequest->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return $this->serverRequest->hasHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        return $this->serverRequest->getHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $serverRequest = $this->serverRequest->withHeader($name, $value);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $serverRequest = $this->serverRequest->withAddedHeader($name, $value);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $serverRequest = $this->serverRequest->withoutHeader($name);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->serverRequest->getBody();
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $serverRequest = $this->serverRequest->withBody($body);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        return $this->serverRequest->getRequestTarget();
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $serverRequest = $this->serverRequest->withRequestTarget($requestTarget);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->serverRequest->getMethod();
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $serverRequest = $this->serverRequest->withMethod($method);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->serverRequest->getUri();
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $serverRequest = $this->serverRequest->withUri($uri, $preserveHost);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->serverRequest->getServerParams();
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        return $this->serverRequest->getCookieParams();
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $serverRequest = $this->serverRequest->withCookieParams($cookies);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        return $this->serverRequest->getQueryParams();
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $serverRequest = $this->serverRequest->withQueryParams($query);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return $this->serverRequest->getUploadedFiles();
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $serverRequest = $this->serverRequest->withUploadedFiles($uploadedFiles);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->serverRequest->getParsedBody();
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        $serverRequest = $this->serverRequest->withParsedBody($data);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->serverRequest->getAttributes();
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $serverRequest = $this->serverRequest->withAttribute($name, $value);

        return new static($serverRequest);
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $serverRequest = $this->serverRequest->withoutAttribute($name);

        return new static($serverRequest);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParam(string $param, $default = null)
    {
        $getParams = $this->getQueryParams();
        $postParams = $this->getParsedBody();

        if (is_array($postParams) && isset($postParams[$param])) {
            return $postParams[$param];
        }

        if (is_object($postParams) && property_exists($postParams, $param)) {
            return $postParams->$param;
        }

        if (isset($getParams[$param])) {
            return $getParams[$param];
        }

        return $default;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParsedBodyParam(string $param, $default = null)
    {
        $postParams = $this->getParsedBody();

        if (is_array($postParams) && isset($postParams[$param])) {
            return $postParams[$param];
        }

        if (is_object($postParams) && property_exists($postParams, $param)) {
            return $postParams->$param;
        }

        return $default;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }
}
