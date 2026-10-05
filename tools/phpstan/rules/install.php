<?php

declare(strict_types=1);

/**
 * Installs the rules PHPStan rule set into a target repository, mirroring
 * anti-slop's install.mjs: copy the directory, refuse to overwrite without
 * --force, and print the composer.json and phpstan.neon snippets to merge.
 * Detects laravel/framework in the target's composer.json "require" (not
 * require-dev, not the lock file) to decide whether to print the Laravel
 * include.
 *
 * Usage: php install.php [target-repository] [--force]
 */

$arguments = array_slice($argv, 1);
$force = in_array('--force', $arguments, true);
$positional = array_values(array_filter($arguments, static fn (string $argument): bool => ! str_starts_with($argument, '--')));

$source = __DIR__;
$targetRepository = $positional[0] ?? getcwd();
if ($targetRepository === false || ! is_dir($targetRepository)) {
    fwrite(STDERR, "Target repository not found: {$targetRepository}\n");
    exit(1);
}

$targetRepository = rtrim($targetRepository, '/');
$destination = $targetRepository.'/tools/phpstan/rules';

if (realpath($destination) === realpath($source)) {
    fwrite(STDERR, "Refusing to copy the rule set onto itself: {$destination}\n");
    exit(1);
}

if (is_dir($destination) && ! $force) {
    fwrite(STDERR, "Refusing to overwrite {$destination}. Re-run with --force only after reviewing the existing files.\n");
    exit(1);
}

$copy = static function (string $from, string $to) use (&$copy): void {
    if (! is_dir($to) && ! mkdir($to, 0755, true) && ! is_dir($to)) {
        throw new RuntimeException("Could not create {$to}");
    }

    $entries = scandir($from);
    if ($entries === false) {
        throw new RuntimeException("Could not read {$from}");
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '.phpunit.cache') {
            continue;
        }

        $fromPath = $from.'/'.$entry;
        $toPath = $to.'/'.$entry;
        if (is_dir($fromPath)) {
            $copy($fromPath, $toPath);
        } elseif (! copy($fromPath, $toPath)) {
            throw new RuntimeException("Could not copy {$fromPath}");
        }
    }
};

$copy($source, $destination);
echo "Copied the rules rule set to {$destination}\n\n";

$composerPath = $targetRepository.'/composer.json';
$requiresLaravel = false;
if (is_file($composerPath)) {
    $composerRaw = file_get_contents($composerPath);
    if ($composerRaw !== false) {
        $composer = json_decode($composerRaw, true);
        $requiresLaravel = is_array($composer)
            && isset($composer['require'])
            && is_array($composer['require'])
            && array_key_exists('laravel/framework', $composer['require']);
    }
}

echo "1. Install current dependency versions (do not pin remembered ones):\n\n";
echo "    composer require --dev phpstan/phpstan phpstan/phpstan-strict-rules \\\n";
echo "        phpstan/extension-installer spaze/phpstan-disallowed-calls symplify/phpstan-rules\n";
if ($requiresLaravel) {
    echo "    composer require --dev larastan/larastan\n";
}

echo "\n2. Merge into composer.json:\n\n";
echo "    \"autoload-dev\": {\n";
echo "        \"psr-4\": { \"Rules\\\\\": \"tools/phpstan/rules/src/\" }\n";
echo "    }\n";

echo "\n3. Merge into phpstan.neon (keep existing excludes; add any other agent\n";
echo "   tooling directories found in the repository — do not broadly exclude\n";
echo "   all dot-directories):\n\n";
echo "    includes:\n";
echo "        - tools/phpstan/rules/extension.neon\n";
if ($requiresLaravel) {
    echo "        - tools/phpstan/rules/laravel/extension.neon\n";
}
echo "    parameters:\n";
echo "        level: 10\n";
echo "        checkImplicitMixed: true\n";
echo "        excludePaths:\n";
echo "            - tools/phpstan/rules\n";
foreach (['.claude', '.cursor', '.agents', '.codex', '.windsurf'] as $agentDirectory) {
    if (is_dir($targetRepository.'/'.$agentDirectory)) {
        echo "            - {$agentDirectory}\n";
    }
}

echo "\n4. Validate: vendor/bin/phpstan analyse\n";
