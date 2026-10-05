<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/app.php';

$kernel = $app->make(Kernel::class);

/*
 * Bootstrap the kernel and prepare application for testing.
 */
$kernel->bootstrap();

$output = new ConsoleOutput;

$prefix = 'database.connections.'.config('database.default');
if (! Str::contains(config("$prefix.database"), 'test')) {
    $output->writeln(PHP_EOL.'<error>Cannot run test process against non-testing database.</error>');
    $output->writeln(PHP_EOL.'<error>Environment is currently pointed at: "'.config("$prefix.database").'".</error>');
    exit(1);
}

/*
 * Perform database migrations and reseeding before continuing with
 * running the tests.
 */
if (! env('SKIP_MIGRATIONS')) {
    $output->writeln(PHP_EOL.'<info>Refreshing database for Integration tests...</info>');
    $kernel->call('migrate:fresh');

    $output->writeln('<info>Seeding database for Integration tests...</info>'.PHP_EOL);
    $kernel->call('db:seed');
} else {
    $output->writeln(PHP_EOL.'<comment>Skipping database migrations...</comment>'.PHP_EOL);
}

// Pop the error and exception handlers the bootstrap-level application installed so
// PHPUnit starts from a clean handler baseline; each test boots its own application.
restore_error_handler();
restore_exception_handler();
