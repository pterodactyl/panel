<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Daemon\FileRepository;
use App\Repositories\Daemon\PowerRepository;
use App\Repositories\Eloquent\EggRepository;
use App\Repositories\Eloquent\NestRepository;
use App\Repositories\Eloquent\NodeRepository;
use App\Repositories\Eloquent\PackRepository;
use App\Repositories\Eloquent\TaskRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Daemon\CommandRepository;
use App\Repositories\Eloquent\ApiKeyRepository;
use App\Repositories\Eloquent\ServerRepository;
use App\Repositories\Eloquent\SessionRepository;
use App\Repositories\Eloquent\SubuserRepository;
use App\Repositories\Eloquent\DatabaseRepository;
use App\Repositories\Eloquent\LocationRepository;
use App\Repositories\Eloquent\ScheduleRepository;
use App\Repositories\Eloquent\SettingsRepository;
use App\Repositories\Eloquent\DaemonKeyRepository;
use App\Repositories\Eloquent\AllocationRepository;
use App\Repositories\Eloquent\PermissionRepository;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Repositories\Daemon\ConfigurationRepository;
use App\Repositories\Eloquent\EggVariableRepository;
use App\Contracts\Repository\NestRepositoryInterface;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Contracts\Repository\PackRepositoryInterface;
use App\Contracts\Repository\TaskRepositoryInterface;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Repositories\Eloquent\DatabaseHostRepository;
use App\Contracts\Repository\ApiKeyRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Repositories\Eloquent\ServerVariableRepository;
use App\Contracts\Repository\SessionRepositoryInterface;
use App\Contracts\Repository\SubuserRepositoryInterface;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\LocationRepositoryInterface;
use App\Contracts\Repository\ScheduleRepositoryInterface;
use App\Contracts\Repository\SettingsRepositoryInterface;
use App\Contracts\Repository\DaemonKeyRepositoryInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Contracts\Repository\PermissionRepositoryInterface;
use App\Contracts\Repository\Daemon\FileRepositoryInterface;
use App\Contracts\Repository\EggVariableRepositoryInterface;
use App\Contracts\Repository\Daemon\PowerRepositoryInterface;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;
use App\Contracts\Repository\Daemon\CommandRepositoryInterface;
use App\Contracts\Repository\ServerVariableRepositoryInterface;
use App\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;
use App\Repositories\Daemon\ServerRepository as DaemonServerRepository;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register all of the repository bindings.
     */
    public function register()
    {
        // Eloquent Repositories
        $this->app->bind(AllocationRepositoryInterface::class, AllocationRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
        $this->app->bind(DaemonKeyRepositoryInterface::class, DaemonKeyRepository::class);
        $this->app->bind(DatabaseRepositoryInterface::class, DatabaseRepository::class);
        $this->app->bind(DatabaseHostRepositoryInterface::class, DatabaseHostRepository::class);
        $this->app->bind(EggRepositoryInterface::class, EggRepository::class);
        $this->app->bind(EggVariableRepositoryInterface::class, EggVariableRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(NestRepositoryInterface::class, NestRepository::class);
        $this->app->bind(NodeRepositoryInterface::class, NodeRepository::class);
        $this->app->bind(PackRepositoryInterface::class, PackRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(ServerRepositoryInterface::class, ServerRepository::class);
        $this->app->bind(ServerVariableRepositoryInterface::class, ServerVariableRepository::class);
        $this->app->bind(SessionRepositoryInterface::class, SessionRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(SubuserRepositoryInterface::class, SubuserRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Daemon Repositories
        $this->app->bind(ConfigurationRepositoryInterface::class, ConfigurationRepository::class);
        $this->app->bind(CommandRepositoryInterface::class, CommandRepository::class);
        $this->app->bind(DaemonServerRepositoryInterface::class, DaemonServerRepository::class);
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(PowerRepositoryInterface::class, PowerRepository::class);
    }
}
