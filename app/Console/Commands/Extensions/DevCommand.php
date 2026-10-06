<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;

#[Description('Build and publish a local extension; --watch republishes each successful build.')]
#[Signature('p:extension:dev {path : Path to the source package.} {--watch : Watch frontend sources for changes.} {--enable : Enable after the initial build.}')]
class DevCommand extends Command
{
    public function handle(ExtensionManifestValidator $validator, InstallsExtensions $installer, ExtensionAssetPublisher $assets): int
    {
        try {
            $manifest = $validator->fromDirectory($this->argument('path'));
            $publish = function () use ($installer, $manifest): void {
                // Republishing its own source over the installed copy is what this command is for.
                $installer->install($manifest->directory, (bool) $this->option('enable'), replace: true);
                $this->components->info('Published '.$manifest->id.'.');
            };
            if (! $manifest->hasUi()) {
                $publish();

                return self::SUCCESS;
            }

            $build = Process::path($manifest->directory)->timeout(300)->run(['npm', 'run', 'build']);
            if ($build->failed()) {
                $this->error($build->output().$build->errorOutput());

                return self::FAILURE;
            }

            $publish();
            if ($this->option('watch')) {
                $assets->watchDevelopment($manifest->id);
                try {
                    $buffer = '';
                    $process = Process::path($manifest->directory)->forever()->start(['npm', 'run', 'dev'], function (string $type, string $output) use (&$buffer, $publish): void {
                        $this->output->write($output);
                        $buffer .= $output;
                        if (preg_match('/built in [^\r\n]+[\r\n]/', $buffer)) {
                            $buffer = '';
                            try {
                                $publish();
                            } catch (InvalidExtensionException $exception) {
                                $this->components->error($exception->getMessage());
                            }
                        }

                        $buffer = mb_substr($buffer, -8192);
                    });

                    $this->trap([SIGINT, SIGTERM], function () use ($process): void {
                        $process->signal(SIGTERM);
                    });

                    return $process->wait()->exitCode() ?? self::FAILURE;
                } finally {
                    $assets->stopDevelopment($manifest->id);
                }
            }
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
