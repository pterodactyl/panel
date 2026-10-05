<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Startup;

use Illuminate\Support\Str;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;

class UpdateStartupVariableRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    private ?EggVariable $variable = null;

    private bool $resolvedVariable = false;

    public function permission(): string
    {
        return Permissions::StartupUpdate->value;
    }

    /**
     * The value is validated with the egg variable's own rules when the variable can be edited.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $variable = $this->filled('key') ? $this->variable() : null;

        return [
            'key' => ['required', 'string'],
            'value' => $variable instanceof EggVariable && $variable->user_viewable && $variable->user_editable
                ? ['present', ...$this->variableRules($variable)]
                : 'present',
        ];
    }

    /**
     * The server's egg variable named by the request, with its server value loaded.
     */
    public function variable(): ?EggVariable
    {
        if (! $this->resolvedVariable) {
            $this->variable = $this->parameter('server', Server::class)
                ->variables()
                ->where('env_variable', $this->string('key')->toString())
                ->first();
            $this->resolvedVariable = true;
        }

        return $this->variable;
    }

    /**
     * Split the variable's pipe-delimited rule string the way the validator would, keeping
     * a regex rule intact.
     *
     * @return list<string>
     */
    private function variableRules(EggVariable $variable): array
    {
        return Str::startsWith($variable->rules, 'regex:') ? [$variable->rules] : explode('|', $variable->rules);
    }
}
