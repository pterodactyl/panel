<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class ExtensionFormFieldRegistry
{
    /**
     * @var array<string, array{model: class-string<Model>, permission: Permissions}>
     */
    public const array FORMS = [
        'admin.user' => ['model' => User::class, 'permission' => Permissions::AdminUsersRead],
        'admin.node' => ['model' => Node::class, 'permission' => Permissions::AdminNodesRead],
        'admin.server' => ['model' => Server::class, 'permission' => Permissions::AdminServersRead],
        'admin.egg' => ['model' => Egg::class, 'permission' => Permissions::AdminEggsRead],
        'admin.location' => ['model' => Location::class, 'permission' => Permissions::AdminLocationsRead],
        'admin.mount' => ['model' => Mount::class, 'permission' => Permissions::AdminMountsRead],
        'admin.databaseHost' => ['model' => DatabaseHost::class, 'permission' => Permissions::AdminDatabaseHostsRead],
    ];

    public const string RULE_KEY_REGEX = '/^[a-z][a-zA-Z0-9_]{0,47}(?:\.(?:\*|[a-zA-Z0-9_]+))*$/';

    /**
     * @var array<string, array<string, ExtensionFormFieldEntry>>
     */
    private array $fields = [];

    /**
     * @param  ValidationRules  $rules
     */
    public function register(string $identifier, string $form, array $rules, ?Closure $load, ?Closure $save, ExtensionRegistration $registration): void
    {
        throw_unless(array_key_exists($form, self::FORMS), InvalidArgumentException::class, sprintf('Extension "%s" registered fields for the unknown form "%s".', $identifier, $form));
        throw_if(isset($this->fields[$form][$identifier]), InvalidArgumentException::class, sprintf('Extension "%s" registered fields for the form "%s" more than once.', $identifier, $form));
        throw_if($rules === [], InvalidArgumentException::class, sprintf('Extension "%s" must register at least one field for the form "%s".', $identifier, $form));
        throw_if((! $load instanceof Closure) !== (! $save instanceof Closure), InvalidArgumentException::class, sprintf('Extension "%s" must provide both a load and a save callback for the form "%s", or neither.', $identifier, $form));
        throw_if(
            ! $load instanceof Closure && ! in_array(self::FORMS[$form]['model'], [User::class, Server::class], true),
            InvalidArgumentException::class,
            sprintf('The form "%s" has no default storage; extension "%s" must provide load and save callbacks.', $form, $identifier),
        );

        $keys = [];
        foreach (array_keys($rules) as $key) {
            throw_unless(preg_match(self::RULE_KEY_REGEX, $key) === 1, InvalidArgumentException::class, sprintf('Extension field rule "%s" must match %s.', $key, self::RULE_KEY_REGEX));
            $keys[explode('.', $key)[0]] = true;
        }

        $this->fields[$form][$identifier] = [
            'rules' => array_map(ExtensionFieldValueGuard::ruleSet(...), $rules),
            'keys' => array_keys($keys),
            'load' => $load,
            'save' => $save,
            'registration' => $registration,
        ];
    }

    /**
     * @return array<string, ExtensionFormFieldEntry>
     */
    public function all(string $form): array
    {
        return array_filter(
            $this->fields[$form] ?? [],
            fn (array $entry): bool => $entry['registration']->isActive(),
        );
    }

    /**
     * @return list<string>
     */
    public function forms(string $identifier): array
    {
        $forms = [];
        foreach (array_keys($this->fields) as $form) {
            if (isset($this->all($form)[$identifier])) {
                $forms[] = $form;
            }
        }

        return $forms;
    }

    /**
     * @return array<string, array<string, ExtensionFormFieldEntry>>
     */
    public function snapshot(): array
    {
        return $this->fields;
    }

    /**
     * @param  array<string, array<string, ExtensionFormFieldEntry>>  $fields
     */
    public function restore(array $fields): void
    {
        $this->fields = $fields;
    }
}
