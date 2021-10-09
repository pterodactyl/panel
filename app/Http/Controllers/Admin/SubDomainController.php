<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SubDomainController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * SubDomainController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(AlertsMessageBag $alert, SettingsRepositoryInterface $settings)
    {
        $this->alert = $alert;
        $this->settings = $settings;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $domains = DB::table('audit_logs')->get();
        $subdomains = DB::table('servers')->get();

        $domains = json_decode(json_encode($domains), true);
        $subdomains = json_decode(json_encode($subdomains), true);

        

        return view('admin.subdomain.index', [
            'settings' => [
                'cf_email' => $this->settings->get('settings::subdomain::cf_email', ''),
                'cf_api_key' => $this->settings->get('settings::subdomain::cf_api_key', ''),
                'max_subdomain' => $this->settings->get('settings::subdomain::max_subdomain', ''),
            ],
            'domains' => $domains,
            'subdomains' => $subdomains
        ]);
    }

    
}
