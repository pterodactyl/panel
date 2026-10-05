<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\DocumentationLinksTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;

uses(TestCase::class);
test('the panel never links to the 1.x documentation', function (): void {
    $legacyLinks = collect([app_path(), resource_path('scripts'), resource_path('views'), resource_path('lang')])
        ->flatMap(fn (string $directory): array => File::allFiles($directory))
        ->filter(fn (SplFileInfo $file): bool => preg_match('#pterodactyl\.io/(panel|wings)/1\.0/#', $file->getContents()) === 1)
        ->map(fn (SplFileInfo $file): string => $file->getRelativePathname())
        ->values()
        ->all();

    expect($legacyLinks)->toBe([]);
});
