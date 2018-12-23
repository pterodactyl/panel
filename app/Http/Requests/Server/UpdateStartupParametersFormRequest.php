<?php

namespace Pterodactyl\Http\Requests\Server;

use Pterodactyl\Http\Requests\FrontendUserFormRequest;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class UpdateStartupParametersFormRequest extends FrontendUserFormRequest
{
    /**
     * @var array
     */
    private $validationAttributes = [];

    /**
     * Determine if the user has permission to update the startup parameters
     * for this server.
     *
     * @return bool
     */
    public function authorize()
    {
        if (! parent::authorize()) {
            return false;
        }

        return $this->user()->can('edit-startup', $this->attributes->get('server'));
    }

    /**
     * Validate that all of the required fields were passed and that the environment
     * variable values meet the defined criteria for those fields.
     *
     * @return array
     */
    public function rules()
    {
        $repository = $this->container->make(EggVariableRepositoryInterface::class);

        $variables = $repository->getEditableVariables($this->attributes->get('server')->egg_id);
        $rules = $variables->mapWithKeys(function ($variable) {
            $this->validationAttributes['environment.' . $variable->env_variable] = $variable->name;

            return ['environment.' . $variable->env_variable => $variable->rules];
        })->toArray();

        return array_merge($rules, [
            'environment' => 'required|array',
        ]);
    }

    /**
     * Return attributes to provide better naming conventions for error messages.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->validationAttributes;
    }
}
