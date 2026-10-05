<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionFailureAttributorTest;

use Illuminate\Support\Facades\File;
use Mockery;
use Pterodactyl\Services\Extensions\ExtensionFailureAttributor;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;
use RuntimeException;
use Throwable;

use function pterodactylTestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-attribution-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/dns/src');
    config(['extensions.enabled' => true]);
    $this->recorded = [];
    $this->repository = Mockery::mock(ExtensionRepository::class);
    $this->repository->shouldReceive('enabled')->andReturn(collect([
        'dns' => ExtensionManifest::fromValidatedData($this->directory.'/dns', ['id' => 'dns', 'name' => 'DNS', 'version' => '1.0.0'], [], null, 'native', null),
    ]));
    $this->repository->shouldReceive('recordFailure')->andReturnUsing(function (string $identifier, string $reason, ?Throwable $exception = null, string $phase = 'runtime'): void {
        $this->recorded[] = [$identifier, $reason, $phase];
    })->byDefault();
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

test('an exception raised by extension code is recorded against that extension', function (): void {
    $thrower = extensionCode('Thrower', 'throw new \RuntimeException("dns cleanup failed");');

    (new ExtensionFailureAttributor($this->repository))->attribute(capture($thrower));

    expect($this->recorded)->toBe([['dns', 'dns cleanup failed', 'runtime']]);
});

test('a core exception that merely passes through extension code stays unattributed', function (): void {
    $wrapper = extensionCode('Wrapper', '$inner();');

    (new ExtensionFailureAttributor($this->repository))->attribute(capture(fn () => $wrapper(function (): never {
        throw new RuntimeException('wings is unreachable');
    })));
    (new ExtensionFailureAttributor($this->repository))->attribute(new RuntimeException('raised by core'));

    expect($this->recorded)->toBe([]);
});

test('attribution is skipped while extensions are disabled and never raises or recurses', function (): void {
    $throwable = capture(extensionCode('Recursive', 'throw new \RuntimeException("failed");'));
    $attributor = new ExtensionFailureAttributor($this->repository);

    config(['extensions.enabled' => false]);
    $attributor->attribute($throwable);
    expect($this->recorded)->toBe([]);

    config(['extensions.enabled' => true]);
    $calls = 0;
    $this->repository->shouldReceive('recordFailure')->andReturnUsing(function () use (&$calls, $attributor, $throwable): void {
        $calls++;
        $attributor->attribute($throwable);

        throw new RuntimeException('recording failed');
    });
    $attributor->attribute($throwable);

    expect($calls)->toBe(1);
});

/** Writes a callable class into the fixture extension's package and returns an instance. */
function extensionCode(string $class, string $body): callable
{
    return (function () use ($class, $body): callable {
        $namespace = 'AttributionProbe'.str_replace('.', '', uniqid('', true));
        $path = $this->directory.'/dns/src/'.$class.'.php';
        File::put($path, "<?php\nnamespace {$namespace};\nfinal class {$class}\n{\n    public function __invoke(?callable \$inner = null): void\n    {\n        {$body}\n    }\n}\n");
        require $path;

        return new ($namespace.'\\'.$class);
    })->call(pterodactylTestCase());
}

function capture(callable $callback): Throwable
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        return $throwable;
    }

    throw new RuntimeException('Expected the callback to throw.');
}
