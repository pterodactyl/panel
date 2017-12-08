<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Requests\Request;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class MailController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    public function index(): View
    {
        return view('admin.settings.mail', [
            'disabled' => $this->config->get('mail.driver') !== 'smtp',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
    }
}
