<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\APILog
 *
 * @property int $id
 * @property bool $authorized
 * @property string|null $error
 * @property string|null $key
 * @property string $method
 * @property string $route
 * @property string|null $content
 * @property string $user_agent
 * @property string $request_ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|APILog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|APILog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|APILog query()
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereAuthorized($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereRequestIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|APILog whereUserAgent($value)
 */
	class APILog extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\AdminRole
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_id
 * @property array $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereSortId($value)
 */
	class AdminRole extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Allocation
 *
 * @property int $id
 * @property int $node_id
 * @property string $ip
 * @property string|null $ip_alias
 * @property int $port
 * @property int|null $server_id
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string $alias
 * @property bool $has_alias
 * @property \Pterodactyl\Models\Server|null $server
 * @property \Pterodactyl\Models\Node $node
 * @property-read string $hashid
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereIpAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Allocation whereUpdatedAt($value)
 */
	class Allocation extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\ApiKey
 *
 * @property int $id
 * @property int $user_id
 * @property int $key_type
 * @property string|null $identifier
 * @property string $token
 * @property array|null $allowed_ips
 * @property string|null $memo
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $r_servers
 * @property int $r_nodes
 * @property int $r_allocations
 * @property int $r_users
 * @property int $r_locations
 * @property int $r_nests
 * @property int $r_eggs
 * @property int $r_database_hosts
 * @property int $r_server_databases
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereAllowedIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereKeyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRAllocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRDatabaseHosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereREggs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRNests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRNodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRServerDatabases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRServers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRUsers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereUserId($value)
 */
	class ApiKey extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\AuditLog
 *
 * @property int $id
 * @property string $uuid
 * @property bool $is_system
 * @property int|null $user_id
 * @property int|null $server_id
 * @property string $action
 * @property string|null $subaction
 * @property array $device
 * @property array $metadata
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User|null $user
 * @property \Pterodactyl\Models\Server|null $server
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereSubaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuditLog whereUuid($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Backup
 *
 * @property int $id
 * @property int $server_id
 * @property string $uuid
 * @property bool $is_successful
 * @property bool $is_locked
 * @property string $name
 * @property string[] $ignored_files
 * @property string $disk
 * @property string|null $checksum
 * @property int $bytes
 * @property string|null $upload_id
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\AuditLog[] $audits
 * @property-read int|null $audits_count
 * @method static \Illuminate\Database\Eloquent\Builder|Backup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Backup newQuery()
 * @method static \Illuminate\Database\Query\Builder|Backup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Backup query()
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereChecksum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereIgnoredFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereIsSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereUploadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Backup whereUuid($value)
 * @method static \Illuminate\Database\Query\Builder|Backup withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Backup withoutTrashed()
 */
	class Backup extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Database
 *
 * @property int $id
 * @property int $server_id
 * @property int $database_host_id
 * @property string $database
 * @property string $username
 * @property string $remote
 * @property string $password
 * @property int $max_connections
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\DatabaseHost $host
 * @method static \Illuminate\Database\Eloquent\Builder|Database newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Database newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Database query()
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereDatabase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereDatabaseHostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereMaxConnections($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereRemote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Database whereUsername($value)
 */
	class Database extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\DatabaseHost
 *
 * @property int $id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $username
 * @property string $password
 * @property int|null $max_databases
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Database[] $databases
 * @property-read int|null $databases_count
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost query()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereHost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereMaxDatabases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseHost whereUsername($value)
 */
	class DatabaseHost extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Egg
 *
 * @property int $id
 * @property string $uuid
 * @property int $nest_id
 * @property string $author
 * @property string $name
 * @property string|null $description
 * @property array|null $features
 * @property string $docker_image -- deprecated, use $docker_images
 * @property string $update_url
 * @property array $docker_images
 * @property array|null $file_denylist
 * @property string|null $config_files
 * @property string|null $config_startup
 * @property string|null $config_logs
 * @property string|null $config_stop
 * @property int|null $config_from
 * @property string|null $startup
 * @property bool $script_is_privileged
 * @property string|null $script_install
 * @property string $script_entry
 * @property string $script_container
 * @property int|null $copy_script_from
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $copy_script_install
 * @property string $copy_script_entry
 * @property string $copy_script_container
 * @property string|null $inherit_config_files
 * @property string|null $inherit_config_startup
 * @property string|null $inherit_config_logs
 * @property string|null $inherit_config_stop
 * @property string $inherit_file_denylist
 * @property array|null $inherit_features
 * @property \Pterodactyl\Models\Nest $nest
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\EggVariable[] $variables
 * @property \Pterodactyl\Models\Egg|null $scriptFrom
 * @property \Pterodactyl\Models\Egg|null $configFrom
 * @property-read int|null $servers_count
 * @property-read int|null $variables_count
 * @method static \Illuminate\Database\Eloquent\Builder|Egg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Egg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Egg query()
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereConfigFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereConfigFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereConfigLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereConfigStartup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereConfigStop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereCopyScriptFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereDockerImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereFileDenylist($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereNestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereScriptContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereScriptEntry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereScriptInstall($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereScriptIsPrivileged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereStartup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereUpdateUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Egg whereUuid($value)
 */
	class Egg extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\EggMount
 *
 * @property int $egg_id
 * @property int $mount_id
 * @method static \Illuminate\Database\Eloquent\Builder|EggMount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EggMount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EggMount query()
 * @method static \Illuminate\Database\Eloquent\Builder|EggMount whereEggId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggMount whereMountId($value)
 */
	class EggMount extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\EggVariable
 *
 * @property int $id
 * @property int $egg_id
 * @property string $name
 * @property string $description
 * @property string $env_variable
 * @property string $default_value
 * @property bool $user_viewable
 * @property bool $user_editable
 * @property string $rules
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property bool $required
 * @property \Pterodactyl\Models\Egg $egg
 * @property \Pterodactyl\Models\ServerVariable $serverVariable
 * 
 * The "server_value" variable is only present on the object if you've loaded this model
 * using the server relationship.
 * @property string|null $server_value
 * @property-read int|null $server_variable_count
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable query()
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereEggId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereEnvVariable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereUserEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EggVariable whereUserViewable($value)
 */
	class EggVariable extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Location
 *
 * @property int $id
 * @property string $short
 * @property string $long
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Node[] $nodes
 * @property \Pterodactyl\Models\Server[] $servers
 * @property-read int|null $nodes_count
 * @property-read int|null $servers_count
 * @method static \Illuminate\Database\Eloquent\Builder|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereUpdatedAt($value)
 */
	class Location extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Mount
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $source
 * @property string $target
 * @property bool $read_only
 * @property bool $user_mountable
 * @property \Pterodactyl\Models\Egg[]|\Illuminate\Database\Eloquent\Collection $eggs
 * @property \Pterodactyl\Models\Node[]|\Illuminate\Database\Eloquent\Collection $nodes
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 * @property-read int|null $eggs_count
 * @property-read int|null $nodes_count
 * @property-read int|null $servers_count
 * @method static \Illuminate\Database\Eloquent\Builder|Mount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Mount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Mount query()
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereReadOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereUserMountable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mount whereUuid($value)
 */
	class Mount extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\MountNode
 *
 * @property int $node_id
 * @property int $mount_id
 * @method static \Illuminate\Database\Eloquent\Builder|MountNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MountNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MountNode query()
 * @method static \Illuminate\Database\Eloquent\Builder|MountNode whereMountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MountNode whereNodeId($value)
 */
	class MountNode extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\MountServer
 *
 * @property int $server_id
 * @property int $mount_id
 * @method static \Illuminate\Database\Eloquent\Builder|MountServer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MountServer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MountServer query()
 * @method static \Illuminate\Database\Eloquent\Builder|MountServer whereMountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MountServer whereServerId($value)
 */
	class MountServer extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Nest
 *
 * @property int $id
 * @property string $uuid
 * @property string $author
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Egg[] $eggs
 * @property-read int|null $eggs_count
 * @property-read int|null $servers_count
 * @method static \Illuminate\Database\Eloquent\Builder|Nest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Nest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Nest query()
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nest whereUuid($value)
 */
	class Nest extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Node
 *
 * @property int $id
 * @property string $uuid
 * @property bool $public
 * @property string $name
 * @property string|null $description
 * @property int $location_id
 * @property int|null $database_host_id
 * @property string $fqdn
 * @property int $listen_port_http
 * @property int $public_port_http
 * @property int $listen_port_sftp
 * @property int $public_port_sftp
 * @property string $scheme
 * @property bool $behind_proxy
 * @property bool $maintenance_mode
 * @property int $memory
 * @property int $memory_overallocate
 * @property int $disk
 * @property int $disk_overallocate
 * @property int $upload_size
 * @property string $daemon_token_id
 * @property string $daemon_token
 * @property string $daemon_base
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Location $location
 * @property \Pterodactyl\Models\Mount[]|\Illuminate\Database\Eloquent\Collection $mounts
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations
 * @property \Pterodactyl\Models\DatabaseHost $databaseHost
 * @property-read int|null $allocations_count
 * @property-read int|null $mounts_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $servers_count
 * @method static \Illuminate\Database\Eloquent\Builder|Node newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Node newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Node query()
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereBehindProxy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDaemonBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDaemonToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDaemonTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDatabaseHostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereDiskOverallocate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereFqdn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereListenPortHttp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereListenPortSftp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereMaintenanceMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereMemory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereMemoryOverallocate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node wherePublicPortHttp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node wherePublicPortSftp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereScheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereUploadSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Node whereUuid($value)
 */
	class Node extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Permission
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission query()
 */
	class Permission extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\PersonalAccessToken
 *
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array|null $abilities
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $tokenable
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereAbilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereTokenableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereTokenableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalAccessToken whereUpdatedAt($value)
 */
	class PersonalAccessToken extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\RecoveryToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecoveryToken whereUserId($value)
 */
	class RecoveryToken extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Schedule
 *
 * @property int $id
 * @property int $server_id
 * @property string $name
 * @property string $cron_day_of_week
 * @property string $cron_month
 * @property string $cron_day_of_month
 * @property string $cron_hour
 * @property string $cron_minute
 * @property bool $is_active
 * @property bool $is_processing
 * @property bool $only_when_online
 * @property \Carbon\Carbon|null $last_run_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\Task[]|\Illuminate\Support\Collection $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule query()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCronDayOfMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCronDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCronHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCronMinute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCronMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereIsProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereLastRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereNextRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereOnlyWhenOnline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereUpdatedAt($value)
 */
	class Schedule extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Server
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $uuidShort
 * @property int $node_id
 * @property string $name
 * @property string $description
 * @property string|null $status
 * @property bool $skip_scripts
 * @property int $owner_id
 * @property int $memory
 * @property int $swap
 * @property int $disk
 * @property int $io
 * @property int $cpu
 * @property string $threads
 * @property bool $oom_disabled
 * @property int $allocation_id
 * @property int $nest_id
 * @property int $egg_id
 * @property string $startup
 * @property string $image
 * @property int $allocation_limit
 * @property int $database_limit
 * @property int $backup_limit
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 * @property \Pterodactyl\Models\Subuser[]|\Illuminate\Database\Eloquent\Collection $subusers
 * @property \Pterodactyl\Models\Allocation $allocation
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations
 * @property \Pterodactyl\Models\Node $node
 * @property \Pterodactyl\Models\Nest $nest
 * @property \Pterodactyl\Models\Egg $egg
 * @property \Pterodactyl\Models\EggVariable[]|\Illuminate\Database\Eloquent\Collection $variables
 * @property \Pterodactyl\Models\Schedule[]|\Illuminate\Database\Eloquent\Collection $schedule
 * @property \Pterodactyl\Models\Database[]|\Illuminate\Database\Eloquent\Collection $databases
 * @property \Pterodactyl\Models\Location $location
 * @property \Pterodactyl\Models\ServerTransfer $transfer
 * @property \Pterodactyl\Models\Backup[]|\Illuminate\Database\Eloquent\Collection $backups
 * @property \Pterodactyl\Models\Mount[]|\Illuminate\Database\Eloquent\Collection $mounts
 * @property \Pterodactyl\Models\AuditLog[] $audits
 * @property-read int|null $allocations_count
 * @property-read int|null $audits_count
 * @property-read int|null $backups_count
 * @property-read int|null $databases_count
 * @property-read int|null $mounts_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $schedule_count
 * @property-read int|null $subusers_count
 * @property-read int|null $variables_count
 * @method static \Illuminate\Database\Eloquent\Builder|Server newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Server newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Server query()
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereAllocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereAllocationLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereBackupLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereCpu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereDatabaseLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereEggId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereIo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereMemory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereNestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOomDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSkipScripts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereStartup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSwap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereThreads($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereUuidShort($value)
 */
	class Server extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\ServerTransfer
 *
 * @property int $id
 * @property int $server_id
 * @property int $old_node
 * @property int $new_node
 * @property int $old_allocation
 * @property int $new_allocation
 * @property array|null $old_additional_allocations
 * @property array|null $new_additional_allocations
 * @property bool|null $successful
 * @property bool $archived
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\Node $oldNode
 * @property \Pterodactyl\Models\Node $newNode
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereNewAdditionalAllocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereNewAllocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereNewNode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereOldAdditionalAllocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereOldAllocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereOldNode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerTransfer whereUpdatedAt($value)
 */
	class ServerTransfer extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\ServerVariable
 *
 * @property int $id
 * @property int $server_id
 * @property int $variable_id
 * @property string $variable_value
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Pterodactyl\Models\EggVariable $variable
 * @property \Pterodactyl\Models\Server $server
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereVariableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServerVariable whereVariableValue($value)
 */
	class ServerVariable extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Session
 *
 * @property string $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $payload
 * @property int $last_activity
 * @method static \Illuminate\Database\Eloquent\Builder|Session newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session query()
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereLastActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereUserId($value)
 */
	class Session extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Setting
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 */
	class Setting extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Subuser
 *
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property array $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 * @property \Pterodactyl\Models\Server $server
 * @property-read string $hashid
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser query()
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subuser whereUserId($value)
 */
	class Subuser extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\Task
 *
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence_id
 * @property string $action
 * @property string $payload
 * @property int $time_offset
 * @property bool $is_queued
 * @property bool $continue_on_failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 * @property \Pterodactyl\Models\Schedule $schedule
 * @property \Pterodactyl\Models\Server $server
 * @method static \Illuminate\Database\Eloquent\Builder|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereContinueOnFailure($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereIsQueued($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereSequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereTimeOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereUpdatedAt($value)
 */
	class Task extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\TaskLog
 *
 * @property int $id
 * @property int $task_id
 * @property \Illuminate\Support\Carbon $run_time
 * @property int $run_status
 * @property string $response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereRunStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereRunTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TaskLog whereUpdatedAt($value)
 */
	class TaskLog extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\User
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string|null $name_first
 * @property string|null $name_last
 * @property string $password
 * @property string|null $remember_token
 * @property string $language
 * @property int|null $admin_role_id
 * @property bool $root_admin
 * @property bool $use_totp
 * @property string|null $totp_secret
 * @property \Illuminate\Support\Carbon|null $totp_authenticated_at
 * @property bool $gravatar
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Pterodactyl\Models\AdminRole|null $adminRole
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\ApiKey[] $apiKeys
 * @property-read int|null $api_keys_count
 * @property-read string $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\RecoveryToken[] $recoveryTokens
 * @property-read int|null $recovery_tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 * @property-read int|null $servers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\UserSSHKey[] $sshKeys
 * @property-read int|null $ssh_keys_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\WebauthnKey[] $webauthnKeys
 * @property-read int|null $webauthn_keys_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAdminRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGravatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNameFirst($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNameLast($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRootAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTotpAuthenticatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTotpSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUseTotp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUuid($value)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\Access\Authorizable, \Illuminate\Contracts\Auth\CanResetPassword {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\UserSSHKey
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $public_key
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSSHKey whereUserId($value)
 */
	class UserSSHKey extends \Eloquent {}
}

namespace Pterodactyl\Models{
/**
 * Pterodactyl\Models\WebauthnKey
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credentialId
 * @property string $type
 * @property array $transports
 * @property string $attestationType
 * @property string $trustPath
 * @property \Ramsey\Uuid\UuidInterface|null $aaguid
 * @property string $credentialPublicKey
 * @property int $counter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $credential_id
 * @property string|null $credential_public_key
 * @property \Webauthn\PublicKeyCredentialSource $public_key_credential_source
 * @property \Webauthn\TrustPath\TrustPath|null $trust_path
 * @property-read \Pterodactyl\Models\User $user
 * @method static \Database\Factories\WebauthnKeyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereAaguid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereAttestationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereCounter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereCredentialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereCredentialPublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereTransports($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereTrustPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebauthnKey whereUserId($value)
 */
	class WebauthnKey extends \Eloquent {}
}

