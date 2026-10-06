<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

/**
 * The action decorators extensions have registered this boot, keyed by action contract.
 * Populated by ExtensionProvider::wrapAction() once a provider has booted successfully.
 *
 * Decorators run each time the container resolves the contract, in registration order,
 * so the extension registered last is the outermost wrapper. A decorator is skipped
 * (the action passes straight through) while its registration is not active or its
 * extension is not enabled. A decorator that throws, or returns something that is not
 * an implementation of the contract, is recorded against its extension and skipped, so
 * a broken extension can never make a core action unresolvable.
 *
 * The object a decorator returns is called directly by core: whatever it throws
 * reaches the caller unchanged, exactly like an exception from the core action, and is
 * attributed to the extension by ExtensionFailureAttributor when it is reported.
 *
 * The contracts that install, enable, disable, remove and configure extensions, and
 * those that apply themes, can never be wrapped: no extender is installed for them, so
 * they always resolve to the panel's own implementation.
 */
final class ExtensionActionDecorators
{
    public const string CONTRACT_NAMESPACE = 'Pterodactyl\\Contracts\\';

    /**
     * Contracts that manage extension and theme packages. A wrapper could veto its own
     * extension's disabling or removal, or change other packages as they are installed.
     */
    public const array PROTECTED_NAMESPACES = [
        'Pterodactyl\\Contracts\\Extensions\\',
        'Pterodactyl\\Contracts\\Themes\\',
    ];

    /** @var array<string, list<array{identifier: string, decorator: Closure, registration: ExtensionRegistration}>> */
    private array $decorators = [];

    /** @var array<string, true> */
    private array $extended = [];

    public function __construct(
        private readonly Application $app,
        private readonly ExtensionRepository $extensions,
    ) {}

    /**
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @param  Closure(TContract): TContract  $decorator
     */
    public function register(string $identifier, string $contract, Closure $decorator, ExtensionRegistration $registration): void
    {
        throw_unless(
            str_starts_with($contract, self::CONTRACT_NAMESPACE) && interface_exists($contract) && $this->app->bound($contract) && ! $this->app->isShared($contract),
            InvalidArgumentException::class,
            sprintf('Extension "%s" can only wrap action contracts under %s that the panel binds per caller; "%s" is not one.', $identifier, self::CONTRACT_NAMESPACE, $contract),
        );
        throw_if(
            array_any(self::PROTECTED_NAMESPACES, fn (string $namespace): bool => str_starts_with(mb_strtolower($contract), mb_strtolower($namespace))),
            InvalidArgumentException::class,
            sprintf('Extension "%s" cannot wrap "%s": the actions that manage extensions and themes are never wrapped.', $identifier, $contract),
        );

        $this->decorators[$contract][] = ['identifier' => $identifier, 'decorator' => $decorator, 'registration' => $registration];
        if (isset($this->extended[$contract])) {
            return;
        }

        // Container extenders cannot be removed, so exactly one is installed per contract
        // and it consults this registry: rolling a registration back only edits the list.
        $this->extended[$contract] = true;
        $this->app->extend($contract, (new ExtensionActionExtender($this, $contract))(...));
    }

    /** @return array<string, list<array{identifier: string, decorator: Closure, registration: ExtensionRegistration}>> */
    public function snapshot(): array
    {
        return $this->decorators;
    }

    /** @param array<string, list<array{identifier: string, decorator: Closure, registration: ExtensionRegistration}>> $decorators */
    public function restore(array $decorators): void
    {
        $this->decorators = $decorators;
    }

    /**
     * Apply every decorator that is currently active to a freshly resolved action.
     *
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @param  TContract  $action
     * @return TContract
     */
    public function decorate(string $contract, object $action): object
    {
        foreach ($this->decorators[$contract] ?? [] as $entry) {
            if (! $entry['registration']->isActive() || ! $this->extensions->isAvailable($entry['identifier'])) {
                continue;
            }

            try {
                $decorated = $entry['decorator']($action);
                throw_unless($decorated instanceof $contract, UnexpectedValueException::class, sprintf('The decorator for %s must return an implementation of that contract.', $contract));
                $action = $decorated;
            } catch (Throwable $throwable) {
                $this->extensions->recordFailure($entry['identifier'], $throwable->getMessage(), $throwable, 'action');
            }
        }

        return $action;
    }
}
