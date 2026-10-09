<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\ArchTest;

use Illuminate\Auth\AuthManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Exceptions\ApiErrorResponse;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Tags\TagBackfiller;
use Pterodactyl\Transformers\Api\Admin\UserTransformer as AdminUserTransformer;
use Pterodactyl\Transformers\Api\Client\UserTransformer as ClientUserTransformer;
use Pterodactyl\Transformers\Concerns\FormatsActivityLogs;
use Throwable;

arch()->preset()->php()->ignoring('Rules');

// md5 is Gravatar's address scheme; sha1 is the
// activity log's public identifier. None of them protect anything.
arch()->preset()->security()->ignoring([
    AdminUserTransformer::class,
    ClientUserTransformer::class,
    FormatsActivityLogs::class,
    'Rules',
]);

arch('strict types and comparisons everywhere')
    ->expect('Pterodactyl')
    ->toUseStrictTypes()
    ->toUseStrictEquality();

arch('no sleeping, debugging, or environment reads outside config')
    ->expect(['sleep', 'usleep', 'dd', 'ddd', 'dump', 'ray', 'exit', 'env'])
    ->not->toBeUsed()
    ->ignoring('Pterodactyl\Tests');

// One rule per namespace: Pest silently passes multi-target expect([...]) here.
foreach ([
    'Pterodactyl\Actions' => [],
    'Pterodactyl\Console' => [],
    'Pterodactyl\Http' => [],
    // Deliberate seams: the tags migration hands the backfiller the connection it
    // runs on, and the extension repository resolves during provider boot.
    'Pterodactyl\Services' => [TagBackfiller::class, ExtensionRepository::class],
] as $namespace => $seams) {
    arch("{$namespace} uses facades for framework services")
        ->expect($namespace)
        ->not->toUse([
            AuthManager::class,
            PasswordBroker::class,
            Dispatcher::class,
            Repository::class,
            Encrypter::class,
            EventDispatcher::class,
            Connection::class,
            ConnectionInterface::class,
            DatabaseManager::class,
        ])
        ->ignoring($seams);
}

arch('controllers')
    ->expect('Pterodactyl\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('only controllers are named Controller')
    ->expect('Pterodactyl')
    ->not->toHaveSuffix('Controller')
    ->ignoring('Pterodactyl\Http\Controllers');

arch('form requests are named Request')
    ->expect('Pterodactyl\Http\Requests')
    ->classes()
    ->toHaveSuffix('Request')
    ->ignoring(RemoteRequestNode::class);

arch('form requests extend FormRequest')
    ->expect('Pterodactyl\Http\Requests')
    ->classes()
    ->toExtend(FormRequest::class)
    ->ignoring(RemoteRequestNode::class);

arch('form requests declare rules')
    ->expect('Pterodactyl\Http\Requests')
    ->classes()
    ->toHaveMethod('rules')
    ->ignoring(RemoteRequestNode::class);

arch('form requests live in Http\Requests')
    ->expect('Pterodactyl')
    ->not->toExtend(FormRequest::class)
    ->ignoring('Pterodactyl\Http\Requests');

arch('middleware')
    ->expect('Pterodactyl\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');

arch('models')
    ->expect('Pterodactyl\Models')
    ->classes()
    ->toExtend(Model::class)
    ->ignoring([
        'Pterodactyl\Models\Attributes',
        'Pterodactyl\Models\Filters',
        'Pterodactyl\Models\Objects',
        'Pterodactyl\Models\Traits',
    ]);

arch('models live in Models')
    ->expect('Pterodactyl')
    ->not->toExtend(Model::class)
    ->ignoring('Pterodactyl\Models');

arch('exceptions')
    ->expect('Pterodactyl\Exceptions')
    ->classes()
    ->toImplement(Throwable::class)
    ->ignoring(ApiErrorResponse::class);

arch('exceptions live in Exceptions')
    ->expect('Pterodactyl')
    ->not->toImplement(Throwable::class)
    ->ignoring('Pterodactyl\Exceptions');

arch('console commands')
    ->expect('Pterodactyl\Console\Commands')
    ->classes()
    ->toHaveSuffix('Command')
    ->toExtend(Command::class)
    ->toHaveMethod('handle');

arch('commands live in Console\Commands')
    ->expect('Pterodactyl')
    ->not->toExtend(Command::class)
    ->ignoring('Pterodactyl\Console\Commands');

arch('jobs')
    ->expect('Pterodactyl\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->toHaveMethod('handle');

arch('notifications')
    ->expect('Pterodactyl\Notifications')
    ->classes()
    ->toExtend(Notification::class);

arch('notifications live in Notifications')
    ->expect('Pterodactyl')
    ->not->toExtend(Notification::class)
    ->ignoring('Pterodactyl\Notifications');

arch('service providers')
    ->expect('Pterodactyl\Providers')
    ->classes()
    ->toHaveSuffix('ServiceProvider')
    ->toExtend(ServiceProvider::class);

// ExtensionProvider is the base class every extension's own provider extends.
arch('service providers live in Providers')
    ->expect('Pterodactyl')
    ->not->toExtend(ServiceProvider::class)
    ->ignoring(['Pterodactyl\Providers', ExtensionProvider::class]);

arch('policies')
    ->expect('Pterodactyl\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('enums and traits')
    ->expect('Pterodactyl\Enum')
    ->toBeEnums();

arch('traits')
    ->expect('Pterodactyl\Traits')
    ->toBeTraits();
