<?php
namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Traits\Controllers\PlainJavascriptInjection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    use PlainJavascriptInjection;

    private Fractal $fractal;
    private DaemonServerRepository $repository;
    private SoftwareVersionService $versionService;
    private NodeRepositoryInterface $nodeRepository;
    private ServerRepositoryInterface $serverRepository;

    public function __construct(
        Fractal $fractal,
        DaemonServerRepository $repository,
        SoftwareVersionService $versionService,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $serverRepository,
    ) {
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->versionService = $versionService;
        $this->serverRepository = $serverRepository;
    }

    public function index(): View
    {
        $nodes = $this->nodeRepository->all();
        $servers = $this->serverRepository->all();
        $allocations = DB::table('allocations')->count();
        $suspended = DB::table('servers')->where('status', 'suspended')->count();

        $memoryUsed = 0;
        $memoryTotal = 0;
        $diskUsed = 0;
        $diskTotal = 0;

        foreach ($nodes as $node) {
            $stats = $this->nodeRepository->getUsageStatsRaw($node);

            $memoryUsed += $stats['memory']['value'];
            $memoryTotal += $stats['memory']['max'];
            $diskUsed += $stats['disk']['value'];
            $diskTotal += $stats['disk']['max'];
        }

        $this->injectJavascript([
            'servers' => $servers,
            'diskUsed' => $diskUsed,
            'diskTotal' => $diskTotal,
            'suspended' => $suspended,
            'memoryUsed' => $memoryUsed,
            'memoryTotal' => $memoryTotal,
        ]);

        return view('admin.jexactyl.index', [
            'version' => $this->versionService,
            'servers' => $servers,
            'allocations' => $allocations,
            'used' => [
                'memory' => $memoryUsed,
                'disk' => $memoryTotal,
            ],
            'available' => [
                'memory' => $memoryTotal,
                'disk' => $diskTotal,
            ],
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(BaseSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::' . $key, $value);
        }

        $this->alert->success('Jexactyl Settings have been updated.')->flash();

        return redirect()->route('admin.settings');
    }
} 