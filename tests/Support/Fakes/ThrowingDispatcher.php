<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use Illuminate\Contracts\Bus\Dispatcher;
use LogicException;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * Dispatcher fake that fails synchronous dispatch with a canned failure.
 *
 * Used to exercise failure paths that depend on dispatchNow() throwing,
 * without reaching for Mockery.
 */
class ThrowingDispatcher implements Dispatcher
{
    /** @var array<string, list<array<int, mixed>>> */
    public array $calls = [];

    public function __construct(private readonly Throwable $failure) {}

    public function assertCalled(string $method): void
    {
        Assert::assertNotEmpty($this->calls[$method] ?? [], "Expected fake to have recorded a call to [{$method}].");
    }

    public function assertNothingCalled(): void
    {
        Assert::assertSame([], $this->calls, 'Expected fake to have recorded no calls.');
    }

    /**
     * @param  mixed  $command
     * @return mixed
     *
     * @throws LogicException
     */
    public function dispatch($command)
    {
        $this->record(__FUNCTION__, $command);

        throw new LogicException('ThrowingDispatcher only supports dispatchNow().');
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     *
     * @throws LogicException
     */
    public function dispatchSync($command, $handler = null)
    {
        $this->record(__FUNCTION__, $command, $handler);

        throw new LogicException('ThrowingDispatcher only supports dispatchNow().');
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     *
     * @throws Throwable
     */
    public function dispatchNow($command, $handler = null)
    {
        $this->record(__FUNCTION__, $command, $handler);

        throw $this->failure;
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return void
     *
     * @throws LogicException
     */
    public function dispatchAfterResponse($command, $handler = null)
    {
        $this->record(__FUNCTION__, $command, $handler);

        throw new LogicException('ThrowingDispatcher only supports dispatchNow().');
    }

    /**
     * QueueingDispatcher-compatibility guard: this fake never queues.
     *
     * @param  mixed  $command
     * @return mixed
     *
     * @throws LogicException
     */
    public function dispatchToQueue($command)
    {
        $this->record(__FUNCTION__, $command);

        throw new LogicException('ThrowingDispatcher only supports dispatchNow().');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>|null  $jobs
     * @return mixed
     *
     * @throws LogicException
     */
    public function chain($jobs = null)
    {
        throw new LogicException('ThrowingDispatcher does not support chaining.');
    }

    /**
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return false;
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler($command)
    {
        return null;
    }

    /**
     * @param  array<int, mixed>  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return $this
     */
    public function map(array $map)
    {
        return $this;
    }

    protected function record(string $method, mixed ...$args): void
    {
        $this->calls[$method][] = array_values($args);
    }
}
