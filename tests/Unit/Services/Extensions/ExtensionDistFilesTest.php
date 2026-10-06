<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionDistFilesTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionDistFiles;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionStylesheetInspector;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-dist-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/dist');
    File::put($this->directory.'/extension.json', json_encode(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js']], JSON_THROW_ON_ERROR));
    File::put($this->directory.'/dist/client.js', 'export default {};');
});

afterEach(fn () => File::deleteDirectory($this->directory));

function put(string $root, string $relative, string $contents = ''): void
{
    File::ensureDirectoryExists(dirname($root.'/'.$relative));
    File::put($root.'/'.$relative, $contents);
}

it('lists every build file, including folders Finder would skip', function () {
    put($this->directory, 'dist/chunks/lazy.js');
    put($this->directory, 'dist/CVS/legacy.css');

    expect(array_keys(ExtensionDistFiles::list($this->directory.'/dist')))->toBe(['CVS/legacy.css', 'chunks/lazy.js', 'client.js']);
});

it('rejects anything that is not a static browser asset', function (string $file, string $reported, string $problem) {
    put($this->directory, 'dist/'.$file, '<?php echo 1;');

    expect(fn () => ExtensionDistFiles::list($this->directory.'/dist'))->toThrow(InvalidExtensionException::class, "dist/{$reported}, which is {$problem}");
})->with([
    'php in a VCS-named folder' => ['CVS/x.php', 'CVS/x.php', 'not a browser asset'],
    'php in a dot folder' => ['.git/y.php', '.git', 'a dotfile'],
    'apache override' => ['.htaccess', '.htaccess', 'a dotfile'],
    'php-fpm override' => ['assets/.user.ini', 'assets/.user.ini', 'a dotfile'],
    'upper-case php' => ['assets/X.PHP', 'assets/X.PHP', 'not a browser asset'],
    'phtml' => ['x.phtml', 'x.phtml', 'not a browser asset'],
    'html document' => ['index.html', 'index.html', 'not a browser asset'],
]);

it('rejects symbolic links', function () {
    symlink($this->directory.'/extension.json', $this->directory.'/dist/manifest.json');

    expect(fn () => ExtensionDistFiles::list($this->directory.'/dist'))->toThrow(InvalidExtensionException::class, 'dist/manifest.json, which is a symbolic link');
});

it('refuses to publish a build with a server-side file and publishes exactly the checked files', function () {
    $publisher = new ExtensionAssetPublisher(new ExtensionStylesheetInspector);
    config()->set('extensions.assets_directory', $this->directory.'/public');

    put($this->directory, 'dist/CVS/x.php', '<?php echo 1;');
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory);
    expect($publisher->unusableBuildReason($manifest))->toBe('Extension "probe" ships dist/CVS/x.php, which is not a browser asset; dist may only contain browser assets.')
        ->and(fn () => $publisher->stage($manifest))->toThrow(InvalidExtensionException::class)
        ->and($this->directory.'/public')->not->toBeDirectory();

    File::delete($this->directory.'/dist/CVS/x.php');
    put($this->directory, 'dist/CVS/legacy.css', 'a{}');
    $version = $publisher->stage($manifest);

    expect(array_keys(ExtensionDistFiles::list($this->directory.'/public/probe/'.$version)))->toBe(['CVS/legacy.css', 'client.js']);
});
