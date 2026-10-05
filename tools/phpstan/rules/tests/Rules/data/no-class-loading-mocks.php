<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoClassLoadingMocks;

interface Clock
{
    public function now(): \DateTimeImmutable;
}

abstract class BaseRepository
{
    abstract public function find(int $id): ?object;
}

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }
}

final class WidgetTest extends \PHPUnit\Framework\TestCase
{
    public function testMocks(): void
    {
        $interfaceMock = $this->createMock(Clock::class);
        $abstractMock = $this->createMock(BaseRepository::class);

        $concreteMock = $this->createMock(SystemClock::class); // error: line 32
        $concreteStub = $this->createStub(SystemClock::class); // error: line 33
        $builder = $this->getMockBuilder(SystemClock::class); // error: line 34

        $mockeryInterface = \Mockery::mock(Clock::class);
        $mockeryConcrete = \Mockery::mock(SystemClock::class); // error: line 37
        $overloaded = \Mockery::mock('overload:Rules\Tests\Data\NoClassLoadingMocks\SystemClock'); // error: line 38
        $aliased = \Mockery::mock('alias:Rules\Tests\Data\NoClassLoadingMocks\SystemClock'); // error: line 39

        \assert($interfaceMock instanceof Clock || true);
        unset($abstractMock, $concreteMock, $concreteStub, $builder, $mockeryInterface, $mockeryConcrete, $overloaded, $aliased);
    }
}

final class NotATest
{
    public function mock(string $class): string
    {
        return $class;
    }

    public function usesOwnMock(): string
    {
        return $this->mock(SystemClock::class);
    }
}
