<?php

namespace Pterodactyl\Services\Hooks;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\HookTriggerValidationException;
use Pterodactyl\Models\Hook;

class HookUpdateService
{
    protected TriggerDefinitionService $triggerDefinitionService;
    /**
     * HookUpdateService constructor.
     */
    public function __construct(TriggerDefinitionService $triggerDefinitionService) {
        $this->triggerDefinitionService = $triggerDefinitionService;
    }

    /**
     * Updates a hook.
     *
     * @throws HookTriggerValidationException
     */
    public function handle(Hook $hook, $params): Hook
    {
        $hook->update(Arr::only($params, ["name", "enabled"]));
        $this->updateTriggers($hook, $params['triggers'] ?? []);
        $this->updateActions($hook, $params['actions'] ?? []);
        return $hook;
    }

    /**
     * @throws HookTriggerValidationException
     */
    protected function updateTriggers(Hook $hook, array $triggers): void
    {
        $errors = [];
        $hook->triggers()->delete();
        foreach ($triggers as $trigger) {
            $definition = $this->triggerDefinitionService->findByKey($trigger['type']);

            if ($definition === null) {
                $errors[] = "Trigger '{$trigger['type']}' not found.";
                continue;
            }

            try {
                $this->triggerDefinitionService->validateConfig($definition, $trigger['config']);
                $hook->triggers()->create($trigger);
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->getErrors());
            }
        }

        if (!empty($errors)) {
            throw new HookTriggerValidationException("One or more triggers are invalid.", $errors);
        }

    }
    protected function updateActions(Hook $hook, array $actions): void
    {
        $hook->actions()->delete();

        foreach ($actions as $action) {
            $hook->actions()->create($action);
        }
    }
}
