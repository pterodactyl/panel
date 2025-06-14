<?php

namespace Pterodactyl\Http\Controllers\Admin\Shop;

use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingsController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * @var string
     */
    protected $tinyLicense = 'ue8ekzcrmmwhdfhtwqft53oh7isolsx57a5apxchyv8r07od';

    /**
     * @var string[]
     */
    public static $currencies = [
        'USD' => '$',
        'EUR' => '€',
        'CZK' => 'Kč',
    ];

    /**
     * CategoriesController constructor.
     * @param AlertsMessageBag $alert
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(AlertsMessageBag $alert, SettingsRepositoryInterface $settingsRepository)
    {
        $this->alert = $alert;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function payments()
    {
        return view('admin.shop.settings.payments', [
            'currencies' => SettingsController::$currencies,
            'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
            'min_amount' => $this->settingsRepository->get('settings::shop::min_amount', 0),
            'max_amount' => $this->settingsRepository->get('settings::shop::max_amount', 0),
            'paypal' => [
                'enabled' => $this->settingsRepository->get('settings::shop::paypal::enabled', 1),
                'mode' => $this->settingsRepository->get('settings::shop::paypal::mode', 'sandbox'),
                'key' => $this->settingsRepository->get('settings::shop::paypal::key', ''),
                'secret' => $this->settingsRepository->get('settings::shop::paypal::secret', ''),
            ],
            'stripe' => [
                'enabled' => $this->settingsRepository->get('settings::shop::stripe::enabled', 1),
                'mode' => $this->settingsRepository->get('settings::shop::stripe::mode', 'sandbox'),
                'key' => $this->settingsRepository->get('settings::shop::stripe::key', ''),
                'secret' => $this->settingsRepository->get('settings::shop::stripe::secret', ''),
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function saveSettings(Request $request)
    {
        $this->validate($request, [
            'currency' => 'required',
            'min_amount' => 'required|numeric',
            'max_amount' => 'required|numeric',
        ]);

        if (!isset(SettingsController::$currencies[trim(strip_tags($request->input('currency', 'USD')))])) {
            throw new DisplayException('Invalid currency.');
        }

        $this->settingsRepository->set('settings::shop::currency', trim(strip_tags($request->input('currency', 'USD'))));
        $this->settingsRepository->set('settings::shop::min_amount', $request->input('min_amount', 0));
        $this->settingsRepository->set('settings::shop::max_amount', $request->input('max_amount', 100));

        $this->alert->success('You\'ve successfully saved the settings.')->flash();

        return redirect()->route('admin.shop.settings.payments');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function savePayments(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
        ]);

        if (!in_array($request->input('type'), ['paypal', 'stripe'])) {
            throw new DisplayException('Invalid type.');
        }

        $this->validate($request, [
            $request->input('type') . '_enabled' => 'min:0|max:1',
            $request->input('type') . '_mode' => 'required',
            $request->input('type') . '_key' => 'required|min:1|max:191',
            $request->input('type') . '_secret' => 'required|min:1|max:191',
        ]);

        if (!in_array($request->input($request->input('type') . '_mode'), ['live', 'sandbox'])) {
            throw new DisplayException('Invalid mode.');
        }

        $this->settingsRepository->set(sprintf('settings::shop::%s::enabled', $request->input('type')), $request->input($request->input('type') . '_enabled'));
        $this->settingsRepository->set(sprintf('settings::shop::%s::mode', $request->input('type')), $request->input($request->input('type') . '_mode'));
        $this->settingsRepository->set(sprintf('settings::shop::%s::key', $request->input('type')), $request->input($request->input('type') . '_key'));
        $this->settingsRepository->set(sprintf('settings::shop::%s::secret', $request->input('type')), $request->input($request->input('type') . '_secret'));

        $this->alert->success('You\'ve successfully edited the payment settings.')->flash();

        return redirect()->route('admin.shop.settings.payments');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function tos()
    {
        return view('admin.shop.settings.tos', [
            'tos_url' => $this->settingsRepository->get('settings::shop::tos_url', ''),
            'tos' => $this->settingsRepository->get('settings::shop::tos', ''),
            'tinyLicense' => $this->tinyLicense,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function saveTos(Request $request)
    {
        $this->validate($request, [
            'tos_url' => 'sometimes',
            'tos' => 'sometimes',
        ]);

        $this->settingsRepository->set('settings::shop::tos_url', $request->input('tos_url', ''));
        $this->settingsRepository->set('settings::shop::tos', $request->input('tos', ''));

        $this->alert->success('You\'ve successfully saved the TOS.')->flash();

        return redirect()->route('admin.shop.settings.tos');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function invoice()
    {
        return view('admin.shop.settings.invoice', [
            'enabled' => $this->settingsRepository->get('settings::shop::invoice::enabled', 0),
            'name' => $this->settingsRepository->get('settings::shop::invoice::name', ''),
            'address' => $this->settingsRepository->get('settings::shop::invoice::address', ''),
            'phone' => $this->settingsRepository->get('settings::shop::invoice::phone', ''),
            'code' => $this->settingsRepository->get('settings::shop::invoice::code', ''),
            'vat' => $this->settingsRepository->get('settings::shop::invoice::vat', ''),
            'tax' => $this->settingsRepository->get('settings::shop::invoice::tax', 0),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function saveInvoice(Request $request)
    {
        $this->validate($request, [
            'enabled' => 'min:0|max:1',
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'code' => 'sometimes',
            'vat' => 'sometimes',
            'tax' => 'required|integer|min:0',
        ]);

        $this->settingsRepository->set('settings::shop::invoice::enabled', $request->input('enabled', 0));
        $this->settingsRepository->set('settings::shop::invoice::name', trim(strip_tags($request->input('name', ''))));
        $this->settingsRepository->set('settings::shop::invoice::address', trim(strip_tags($request->input('address', ''))));
        $this->settingsRepository->set('settings::shop::invoice::phone', trim(strip_tags($request->input('phone', ''))));
        $this->settingsRepository->set('settings::shop::invoice::code', trim(strip_tags($request->input('code', ''))));
        $this->settingsRepository->set('settings::shop::invoice::vat', trim(strip_tags($request->input('vat', ''))));
        $this->settingsRepository->set('settings::shop::invoice::tax', trim(strip_tags($request->input('tax', 0))));

        $this->alert->success('You\'ve successfully saved the invoice settings.')->flash();

        return redirect()->route('admin.shop.settings.invoice');
    }
	
	/**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function servers(Request $request)
    {
        return view('admin.shop.settings.servers', [
            'deleteDays' => $this->settingsRepository->get('settings::shop::servers::days', 0),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function saveServerSettings(Request $request)
    {
        $this->validate($request, [
            'deleteDays' => 'required|integer|min:0',
        ]);

        $this->settingsRepository->set('settings::shop::servers::days', (int) $request->input('deleteDays'));

        $this->alert->success('You\'ve successfully saved the server settings.')->flash();

        return redirect()->route('admin.shop.settings.servers');
    }
}
