<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/app.php';

/** @var \Pterodactyl\Console\Kernel $kernel */
$kernel = $app->make(Kernel::class);

/*
 * Bootstrap the kernel and prepare application for testing.
 */
$kernel->bootstrap();

$output = new ConsoleOutput;

/*
 * Perform database migrations and reseeding before continuing with
 * running the tests.
 */
$output->writeln(PHP_EOL . '<comment>Refreshing database for Integration tests...</comment>');
$kernel->call('migrate:fresh', ['--database' => 'testing']);

$output->writeln('<comment>Seeding database for Integration tests...</comment>' . PHP_EOL);
$kernel->call('db:seed', ['--database' => 'testing']);
