<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

/**
 * The single container extender installed for a wrapped action contract. It carries no
 * decorators of its own: every resolution asks the registry which ones currently apply.
 *
 * @template TContract of object
 */
final readonly class ExtensionActionExtender
{
    /** @param class-string<TContract> $contract */
    public function __construct(
        private ExtensionActionDecorators $decorators,
        private string $contract,
    ) {}

    /**
     * @param  TContract  $action
     * @return TContract
     */
    public function __invoke(object $action): object
    {
        return $this->decorators->decorate($this->contract, $action);
    }
}
