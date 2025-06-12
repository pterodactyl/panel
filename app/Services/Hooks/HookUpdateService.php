<?php

namespace Pterodactyl\Services\Hooks;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\HookActionValidationException;
use Pterodactyl\Exceptions\HookTriggerValidationException;
use Pterodactyl\Models\Hook;

class HookUpdateService
{
    protected TriggerDefinitionService $triggerDefinitionService;
    protected ActionDefinitionService $actionDefinitionService;

    /**
     * HookUpdateService constructor.
     */
    public function __construct(TriggerDefinitionService $triggerDefinitionService, ActionDefinitionService $actionDefinitionService) {
        $this->triggerDefinitionService = $triggerDefinitionService;
        $this->actionDefinitionService = $actionDefinitionService;
    }

    /**
     * Updates a hook.
     *
     * @throws HookTriggerValidationException
     * @throws HookActionValidationException
     */
    public function handle(Hook $hook, $params): Hook
    {
        $hook->update(Arr::only($params, ["name", "enabled"]));
        $this->updateTrigger($hook, $params['trigger'] ?? []);
        $this->updateAction($hook, $params['action'] ?? []);
        return $hook;
    }

    /**
     * @throws HookTriggerValidationException
     */
    protected function updateTrigger(Hook $hook, array $trigger): void
    {
        $errors = [];
        $hook->trigger()->delete();

        $definition = $this->triggerDefinitionService->findByKey($trigger['type']);

        if ($definition === null) {
            $errors[] = "Trigger '{$trigger['type']}' not found.";
            return;
        }

        try {
            $this->triggerDefinitionService->validateConfig($definition, $trigger['config']);
            $trigger['trigger_definition_id'] = $definition->id;
            $hook->trigger()->create($trigger);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->validator->errors()->toArray());
        }

        if (!empty($errors)) {
            throw new HookTriggerValidationException("One or more triggers are invalid.", $errors);
        }

    }

    /**
     * @throws HookActionValidationException
     */
    protected function updateAction(Hook $hook, array $action): void
    {
        $errors = [];
        $hook->action()->delete();
        $definition = $this->actionDefinitionService->findByKey($action['type']);

        if ($definition === null) {
            $errors[] = "Action not found. " . json_encode($action);
            return;
        }


        try {
            $this->actionDefinitionService->validateConfig($definition, $action['config']);
            $action['action_definition_id'] = $definition->id;
            $hook->action()->create($action);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->validator->errors()->toArray());
        }
        if (!empty($errors)) {
            throw new HookActionValidationException("One or more actions are invalid.", $errors);
        }

    }
}
