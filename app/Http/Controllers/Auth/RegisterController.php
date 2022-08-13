<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Auth\RegisterRequest;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class RegisterController extends AbstractLoginController
{
    private UserCreationService $creationService;
    private SettingsRepositoryInterface $settings;

    /**
     * RegisterController constructor.
     */
    public function __construct(
        UserCreationService $creationService,
        SettingsRepositoryInterface $settings,
    ) {
        $this->settings = $settings;
        $this->creationService = $creationService;
    }
    /**
     * Handle a register request to the application.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        if ($this->settings->get('jexactyl::registration:enabled') != 'true') {
            throw new DisplayException('Unable to register: Registration is currently disabled.');
        };

        $prefix = 'jexactyl::registration:';
        $approval = true;

        if ($this->settings->get('jexactyl::approvals:enabled') == 'true') {
            $approval = false;
        };

        $data = [
            'email' => $request->input('email'),
            'username' => $request->input('user'),
            'name_first' => 'Jexactyl',
            'name_last' => 'User',
            'password' => $request->input('password'),
            'ip' => $request->getClientIp(),
            'store_cpu' => $this->settings->get($prefix.'cpu', 0),
            'store_memory' => $this->settings->get($prefix.'memory', 0),
            'store_disk' => $this->settings->get($prefix.'disk', 0),
            'store_slots' => $this->settings->get($prefix.'slot', 0),
            'store_ports' => $this->settings->get($prefix.'port', 0),
            'store_backups' => $this->settings->get($prefix.'backup', 0),
            'store_databases' => $this->settings->get($prefix.'database', 0),
            'approved' => $approval,
        ];

        $this->creationService->handle($data);

        return new JsonResponse([
            'data' => [
                'complete' => true,
                'intended' => $this->redirectPath(),
            ],
        ]);
    }
}
