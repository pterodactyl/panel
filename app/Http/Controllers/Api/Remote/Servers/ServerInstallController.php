<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Events\Server\Installed as ServerInstalled;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Pterodactyl\Http\Requests\Api\Remote\InstallationDataRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Minecraft\MinecraftSoftwareService;
use Pterodactyl\Services\Servers\StartupModificationService;

class ServerInstallController extends Controller
{
    /**
     * ServerInstallController constructor.
     */
    public function __construct(private ServerRepository $repository, private EventDispatcher $eventDispatcher,
        private MinecraftSoftwareService $minecraftSoftwareService, private StartupModificationService $startupModificationService)
    {
    }

    /**
     * Returns installation information for a server.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);
        $egg = $server->egg;

        return new JsonResponse([
            'container_image' => $egg->copy_script_container,
            'entrypoint' => $egg->copy_script_entry,
            'script' => $egg->copy_script_install,
        ]);
    }

    /**
     * Updates the installation state of a server.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(InstallationDataRequest $request, string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);
        $status = null;

        // Make sure the type of failure is accurate
        if (!$request->boolean('successful')) {
            $status = Server::STATUS_INSTALL_FAILED;

            if ($request->boolean('reinstall')) {
                $status = Server::STATUS_REINSTALL_FAILED;
            }
        } else {
            $this->updateServerImageFromMinecraftSoftware($server);
        }

        // Keep the server suspended if it's already suspended
        if ($server->status === Server::STATUS_SUSPENDED) {
            $status = Server::STATUS_SUSPENDED;
        }

        $this->repository->update($server->id, ['status' => $status, 'installed_at' => CarbonImmutable::now()], true, true);

        // If the server successfully installed, fire installed event.
        // This logic allows individually disabling install and reinstall notifications separately.
        $isInitialInstall = is_null($server->installed_at);
        if ($isInitialInstall && config()->get('pterodactyl.email.send_install_notification', true)) {
            $this->eventDispatcher->dispatch(new ServerInstalled($server));
        } elseif (!$isInitialInstall && config()->get('pterodactyl.email.send_reinstall_notification', true)) {
            $this->eventDispatcher->dispatch(new ServerInstalled($server));
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    protected function updateServerImageFromMinecraftSoftware(Server $server) {
        try {
            if (DB::table('modpack_installations')
                ->where('server_id', $server->id)
                ->where('finalized', false)
                ->exists()) {

                    // Update Java Docker image depending on the detected Minecraft version.
                    $this->minecraftSoftwareService->setServer($server);
                    $buildInfo = $this->minecraftSoftwareService->getServerBuildInformation();

                    if (isset($buildInfo['java'])) {
                        $availableImages = $server->egg->docker_images;
                        $newImage = $this->getImageForJavaVersion($availableImages, $buildInfo['java']) ?? 'ghcr.io/pterodactyl/yolks:java_' . $buildInfo['java'];
                        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN)->handle($server, [
                            'docker_image' => $newImage,
                        ]);
                    }
                    DB::table('modpack_installations')
                        ->where('server_id', $server->id)
                        ->where('finalized', false)
                        ->update(['finalized' => true]);
                }
            } catch (Exception) {}
    }

    protected function getImageForJavaVersion(array $availableImages, string $javaVersion): ?string
    {
        if (function_exists('array_find')) {
            return array_find($availableImages, fn ($v, $k) => str_ends_with($k, ' ' . $javaVersion));
        }
        foreach ($availableImages as $name => $avImage) {
            if (str_ends_with($name, ' ' . $javaVersion)) {
                return $avImage;
            }
        }
        return null;
    }
}
