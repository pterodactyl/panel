<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Support;

use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Support\JsonValueGuard;
use ReflectionMethod;

class PterodactylDocumentation
{
    public const array VALIDATION_ERROR = [
        'errors' => [
            [
                'code' => 'ValidationException',
                'status' => '422',
                'detail' => 'The submitted data is invalid.',
                'source' => ['field' => 'field'],
            ],
        ],
    ];

    public const array AUTHENTICATION_ERROR = [
        'errors' => [
            [
                'code' => 'Unauthenticated',
                'status' => '401',
                'detail' => 'Authentication credentials were missing or invalid.',
            ],
        ],
    ];

    public const array FORBIDDEN_ERROR = [
        'errors' => [
            [
                'code' => 'Forbidden',
                'status' => '403',
                'detail' => 'The API key does not have permission to perform this action.',
            ],
        ],
    ];

    public const array NOT_FOUND_ERROR = [
        'errors' => [
            [
                'code' => 'NotFoundHttpException',
                'status' => '404',
                'detail' => 'The requested resource could not be found.',
            ],
        ],
    ];

    private const array URL_PARAMETERS = [
        'allocation' => ['integer', 'The allocation ID.', 1],
        'backup' => ['string', 'The backup UUID.', 'b6b9acf9-19b1-4bb1-8c75-4abac802c6cf'],
        'backup_uuid' => ['string', 'The backup UUID.', 'b6b9acf9-19b1-4bb1-8c75-4abac802c6cf'],
        'database' => ['string', 'The database ID.', 'Lk3jx4pQ'],
        'database_id' => ['string', 'The database ID.', 'Lk3jx4pQ'],
        'database_host' => ['integer', 'The database host ID.', 1],
        'databaseHost' => ['integer', 'The database host ID.', 1],
        'egg' => ['integer', 'The egg ID.', 1],
        'egg_id' => ['integer', 'The egg ID.', 1],
        'external_id' => ['string', 'The external ID.', 'external-identifier'],
        'extension' => ['string', 'The extension identifier.', 'probe'],
        'file' => ['string', 'The file path.', '/server.properties'],
        'force' => ['string', 'Whether to force the delete operation.', 'force'],
        'id' => ['integer', 'The resource ID.', 1],
        'identifier' => ['string', 'The server UUID or short UUID.', 'd3aac109'],
        'input' => ['string', 'The input name of the extension setting.', 'logo'],
        'job' => ['string', 'The progress job UUID.', '11111111-1111-4111-8111-111111111111'],
        'key' => ['string', 'The API key identifier.', 'ci0abc123def'],
        'location' => ['integer', 'The location ID.', 1],
        'location_id' => ['integer', 'The location ID.', 1],
        'mount' => ['integer', 'The mount ID.', 1],
        'mount_id' => ['integer', 'The mount ID.', 1],
        'node' => ['integer', 'The node ID.', 1],
        'node_id' => ['integer', 'The node ID.', 1],
        'schedule' => ['integer', 'The schedule ID.', 1],
        'schedule_id' => ['integer', 'The schedule ID.', 1],
        'server' => ['string', 'The server UUID.', 'd3aac109-e5a0-4331-b03e-3454f7e02bbe'],
        'server_id' => ['integer', 'The server ID.', 1],
        'server_uuid' => ['string', 'The server UUID.', 'd3aac109-e5a0-4331-b03e-3454f7e02bbe'],
        'server_admin_identifier' => ['string', 'The server ID or UUID.', '1'],
        'subuser' => ['integer', 'The subuser ID.', 1],
        'task' => ['integer', 'The task ID.', 1],
        'task_id' => ['integer', 'The task ID.', 1],
        'user' => ['integer', 'The user ID.', 1],
        'user_id' => ['integer', 'The user ID.', 1],
        'variable' => ['integer', 'The variable ID.', 1],
        'variable_id' => ['integer', 'The variable ID.', 1],
    ];

    private const array FIELD_DESCRIPTIONS = [
        'action' => 'The action to perform.',
        'alias' => 'An optional alias for this resource.',
        'allocation' => 'The allocation ID.',
        'allocation_id' => 'The allocation ID.',
        'allocation_limit' => 'The maximum number of allocations allowed.',
        'allowed_ips' => 'IP addresses allowed to use this key.',
        'backup_limit' => 'The maximum number of backups allowed.',
        'behind_proxy' => 'Whether the node is behind a proxy.',
        'command' => 'The console command to send.',
        'contents' => 'The file contents.',
        'continue_on_failure' => 'Whether the schedule continues when this task fails.',
        'copy_script_from' => 'Another egg ID to copy the install script from.',
        'cpu' => 'The CPU limit percentage.',
        'current_password' => 'The current account password.',
        'database_host_id' => 'The database host ID.',
        'database_limit' => 'The maximum number of databases allowed.',
        'default_value' => 'The default value.',
        'directory' => 'The directory path.',
        'disk' => 'The disk limit in megabytes.',
        'description' => 'A description for this resource.',
        'docker_image' => 'The Docker image to use.',
        'docker_images' => 'Docker images available to this resource.',
        'egg_id' => 'The egg ID.',
        'email' => 'The user email address.',
        'env_variable' => 'The environment variable name.',
        'environment' => 'Environment variables for the server.',
        'external_id' => 'An external identifier for this resource.',
        'feature_limits.allocations' => 'The maximum number of allocations allowed.',
        'feature_limits.backups' => 'The maximum number of backups allowed.',
        'feature_limits.databases' => 'The maximum number of databases allowed.',
        'features' => 'Feature flags enabled for this resource.',
        'file' => 'The file path.',
        'file_denylist' => 'File patterns denied by the file manager.',
        'filename' => 'The file name.',
        'files' => 'File paths affected by the operation.',
        'fingerprint' => 'The SSH key fingerprint.',
        'force_outgoing_ip' => 'Whether servers should use their allocation IP for outgoing traffic.',
        'fqdn' => 'The fully qualified domain name.',
        'host' => 'The database host address.',
        'ignored' => 'Whether this item should be ignored.',
        'import_file' => 'The file to import.',
        'io' => 'The block IO weight.',
        'ip' => 'The IP address.',
        'is_active' => 'Whether the resource is active.',
        'is_locked' => 'Whether the resource is locked.',
        'language' => 'The locale code.',
        'location_id' => 'The location ID.',
        'max_connections' => 'The maximum number of connections allowed.',
        'memory' => 'The memory limit in megabytes.',
        'mount_id' => 'The mount ID.',
        'name' => 'The display name.',
        'node_id' => 'The node ID.',
        'notes' => 'Notes for this resource.',
        'oom_disabled' => 'Whether the OOM killer is disabled.',
        'options' => 'Additional options for this resource.',
        'order' => 'Resource IDs in the desired display order.',
        'owner_id' => 'The owner user ID.',
        'password' => 'The password.',
        'password_confirmation' => 'The password confirmation.',
        'permissions' => 'Permissions granted to the user.',
        'port' => 'The port number.',
        'ports' => 'Ports to allocate.',
        'primary_allocation_id' => 'The primary allocation ID.',
        'public_key' => 'The SSH public key.',
        'remote' => 'The remote database address.',
        'root_admin' => 'Whether the user has administrator privileges.',
        'rules' => 'Laravel validation rules applied to user-submitted values.',
        'script_container' => 'The install container image.',
        'script_entry' => 'The install container entrypoint.',
        'script_install' => 'The install script body.',
        'script_is_privileged' => 'Whether the install script runs in privileged mode.',
        'sequence_id' => 'The task execution order.',
        'signal' => 'The power signal.',
        'skip_scripts' => 'Whether install scripts should be skipped.',
        'source' => 'The source path.',
        'start_on_completion' => 'Whether the server should start when the operation completes.',
        'startup' => 'The startup command.',
        'swap' => 'The swap limit in megabytes.',
        'target' => 'The target path.',
        'threads' => 'CPU threads available to the server.',
        'time_offset' => 'The delay before this task runs.',
        'url' => 'The URL.',
        'user' => 'The user ID.',
        'user_mountable' => 'Whether users can mount this resource.',
        'username' => 'The username.',
    ];

    private const array FIELD_TYPES = [
        'allocation_additional' => 'integer[]',
        'allocation_id' => 'integer',
        'archived' => 'boolean',
        'environment' => 'object<string,string>',
        'extends' => 'integer',
        'egg_id' => 'integer',
        'features' => 'string[]',
        'file_denylist' => 'string[]',
        'location_id' => 'integer',
        'max_connections' => 'integer',
        'server_id' => 'integer',
        'primary_allocation_id' => 'integer',
        'secondary_allocations_ids' => 'integer[]',
        'successful' => 'boolean',
    ];

    /**
     * @return array{type: string, description: string, example: int|string}|null
     */
    public static function urlParameter(string $name): ?array
    {
        if (! isset(self::URL_PARAMETERS[$name])) {
            return null;
        }

        [$type, $description, $example] = self::URL_PARAMETERS[$name];

        return ['type' => $type, 'description' => $description, 'example' => $example];
    }

    public static function fieldDescription(string $name): string
    {
        return self::FIELD_DESCRIPTIONS[$name]
            ?? 'The '.str_replace(['_', '.'], ' ', $name).'.';
    }

    public static function fieldType(string $name): ?string
    {
        return self::FIELD_TYPES[$name] ?? null;
    }

    /** @return ApiValue1 */
    public static function fieldExample(string $name, string $type): bool|float|int|string|array|null
    {
        return match (true) {
            str_starts_with($name, 'feature_limits.'), str_starts_with($name, 'limits.') => 1,
            str_starts_with($name, 'deploy.') && str_contains($name, 'locations') => [1],
            str_starts_with($name, 'deploy.') && str_contains($name, 'port_range') => ['25565-25570'],
            str_starts_with($name, 'smtp.') && str_contains($name, 'port') => 587,
            str_starts_with($name, 'smtp.') && str_contains($name, 'host') => 'smtp.example.com',
            $name === 'options' => ['user_viewable', 'user_editable'],
            str_contains($type, '[]') && $name === 'order' => [3, 1, 2],
            str_contains($type, '[]') && str_contains($name, 'location') => [1, 2],
            str_contains($type, '[]') && str_contains($name, 'port') => ['25565-25570'],
            str_contains($type, '[]') && str_contains($name, 'permission') => ['control.console', 'control.start'],
            str_contains($type, '[]') && str_contains($name, 'file') => ['/server.properties'],
            $type === 'object<string,string>' => ['SERVER_JARFILE' => 'server.jar'],
            str_contains($type, '[]') => ['example'],
            str_contains($type, 'boolean') || $type === 'bool' => true,
            str_contains($type, 'integer') || $type === 'int' => 1,
            str_contains($type, 'number') || $type === 'float' => 1.5,
            str_contains($name, 'allocation') => 1,
            str_contains($name, 'cpu') => 100,
            str_contains($name, 'disk') => 10240,
            str_contains($name, 'email') => 'admin@example.com',
            str_contains($name, 'memory') => 1024,
            str_contains($name, 'password') => 'correct horse battery staple',
            str_contains($name, 'port') => 25565,
            str_contains($name, 'uuid') => 'd3aac109-e5a0-4331-b03e-3454f7e02bbe',
            $name === 'command' => 'say Server restarting in 5 minutes',
            $name === 'contents' => 'server-port=25565',
            $name === 'default_value' => 'server.jar',
            $name === 'description' => 'A short description.',
            $name === 'directory' => '/config',
            $name === 'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21',
            $name === 'env_variable' => 'SERVER_JARFILE',
            $name === 'file' => '/server.properties',
            $name === 'filename' => 'server.properties',
            $name === 'fqdn' => 'node.example.com',
            $name === 'host' || $name === 'remote' => '127.0.0.1',
            $name === 'import_file' => 'egg-minecraft.json',
            $name === 'ip' => '192.0.2.10',
            $name === 'name' => 'Example Resource',
            $name === 'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIExampleKey user@example.com',
            $name === 'rules' => 'required|string|max:64',
            $name === 'signal' => 'start',
            $name === 'source' || $name === 'target' => '/server.properties',
            $name === 'startup' => 'java -Xms128M -XX:MaxRAMPercentage=95.0 -jar {{SERVER_JARFILE}}',
            $name === 'url' => 'https://example.com/server.jar',
            default => 'example',
        };
    }

    public static function methodSource(ReflectionMethod $method): string
    {
        $file = $method->getFileName();
        if ($file === false) {
            return '';
        }

        $lines = file($file);
        if ($lines === false) {
            return '';
        }

        $start = max(0, $method->getStartLine() - 1);
        $length = $method->getEndLine() - $method->getStartLine() + 1;

        return implode('', array_slice($lines, $start, $length));
    }

    /**
     * @param  JsonInputValue  $value
     * @return DeepOpenApiSchema an OpenAPI schema fragment describing the value
     */
    public static function schemaForValue(mixed $value): array
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                $properties = [];
                foreach ($value as $key => $item) {
                    $properties[$key] = self::schemaForValue($item);
                    if ($item === null && is_string($key) && ($type = self::fieldType($key)) !== null) {
                        $properties[$key]['type'] = match ($type) {
                            'integer', 'int' => 'integer',
                            'number', 'float' => 'number',
                            'boolean', 'bool' => 'boolean',
                            default => $type,
                        };
                    }
                }

                return self::openApiSchema([
                    'type' => 'object',
                    'required' => array_keys($properties),
                    'properties' => $properties,
                ]);
            }

            return self::openApiSchema([
                'type' => 'array',
                'items' => isset($value[0]) ? self::schemaForValue($value[0]) : ['type' => 'string'],
            ]);
        }

        if ($value === null) {
            return self::openApiSchema([
                'type' => 'string',
                'nullable' => true,
            ]);
        }

        if ($value instanceof JsonEmptyObject) {
            return self::openApiSchema([
                'type' => 'object',
                'properties' => new JsonEmptyObject,
            ]);
        }

        return self::openApiSchema(match (gettype($value)) {
            'boolean' => ['type' => 'boolean', 'example' => $value],
            'integer' => ['type' => 'integer', 'example' => $value],
            'double' => ['type' => 'number', 'example' => $value],
            default => ['type' => 'string', 'example' => (string) $value],
        });
    }

    /**
     * @phpstan-assert DeepOpenApiSchema $schema
     *
     * @return DeepOpenApiSchema
     */
    private static function openApiSchema(mixed $schema): array
    {
        JsonValueGuard::assertPayload9($schema);

        return $schema;
    }
}
