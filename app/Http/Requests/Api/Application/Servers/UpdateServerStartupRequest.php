<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Actions\Servers\UpdateServerStartup;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class UpdateServerStartupRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_SERVERS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules to run the input against. The Application API keeps its own field
     * names; actionInput() hands the values to the action under its names.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = UpdateServerStartup::rules(User::USER_LEVEL_ADMIN);

        return [
            'startup' => $rules['startup'],
            'environment' => $rules['environment'],
            'egg' => $rules['egg_id'],
            'image' => $rules['docker_image'],
            'skip_scripts' => $rules['skip_scripts'],
        ];
    }

    /**
     * The validated input under the action's field names: egg and image are its egg_id
     * and docker_image.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $input = parent::actionInput();
        $input['egg_id'] = $input['egg'] ?? null;
        $input['docker_image'] = $input['image'] ?? null;
        unset($input['egg'], $input['image']);

        return $input;
    }
}
