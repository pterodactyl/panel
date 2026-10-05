<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\Scaffolding\ExtensionScaffolder;

#[Description('Scaffold a new extension package: manifest, provider, a client API route with Scribe attributes, and an SDK frontend.')]
#[Signature('p:extension:make
                            {id : The extension identifier (lowercase slug, e.g. my-extension).}
                            {--name= : Display name (defaults to the id in headline case).}
                            {--description= : Manifest description.}
                            {--author= : Manifest author.}
                            {--prefix= : Tailwind prefix for the classes of the extension: 2-12 lowercase letters (defaults to one derived from the id).}
                            {--no-ui : Skip the frontend scaffold (backend-only extension).}
                            {--out= : Directory to scaffold into (defaults to the extensions directory).}
                            {--force : Overwrite the target directory if it already exists.}')]
class MakeCommand extends Command implements PromptsForMissingInput
{
    public function handle(ExtensionScaffolder $scaffolder): int
    {
        try {
            $target = $scaffolder->scaffold(
                id: $this->argument('id'),
                name: $this->option('name'),
                description: $this->option('description'),
                author: $this->option('author'),
                ui: ! $this->option('no-ui'),
                outDir: $this->option('out'),
                force: (bool) $this->option('force'),
                prefix: $this->option('prefix'),
            );
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Extension scaffolded at {$target}.");

        foreach ($scaffolder->files() as $file) {
            $this->components->twoColumnDetail($file, '<fg=green>created</>');
        }

        $this->line('');
        $this->components->info('Next steps:');
        $this->components->bulletList(array_filter([
            $this->option('no-ui') ? null : "cd {$target} && npm install && npm run build",
            "php artisan p:extension:install {$target} --enable",
        ]));

        return self::SUCCESS;
    }

    /** @return array<string, array<int, string>> */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'id' => ['What should the extension be identified as?', 'E.g. my-extension'],
        ];
    }
}
