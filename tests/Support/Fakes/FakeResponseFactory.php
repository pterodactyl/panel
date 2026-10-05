<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

/**
 * Records view() calls and returns a real response since the
 * errors.maintenance blade is provided by the frontend theme at runtime.
 */
class FakeResponseFactory implements ResponseFactory
{
    /** @var list<string|array> */
    public array $views = [];

    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    public function noContent($status = 204, array $headers = [])
    {
        return new Response('', $status, $headers);
    }

    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        $this->views[] = $view;

        return new Response('', $status, $headers);
    }

    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function eventStream(Closure $callback, array $headers = [], \Illuminate\Http\StreamedEvent|string|null $endStreamWith = '</stream>')
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function stream($callback, $status = 200, array $headers = [])
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function file($file, array $headers = [])
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        throw new BadMethodCallException('Not implemented.');
    }
}
