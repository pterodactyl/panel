<?php

namespace Pterodactyl\Http\Controllers\Admin\Shop;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentsController extends Controller
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * PaymentsController constructor.
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $payments = DB::table('payments')
            ->orderBy('created_at', 'DESC')
            ->where('completed', '=', 1)
            ->leftJoin('users', 'users.id', '=', 'payments.user_id')
            ->select(['payments.*', 'users.name_last', 'users.name_first'])
            ->paginate(20);

        $allPayment = DB::table('payments')->where('completed', '=', 1)->get();

        $total = 0;
        $currentMonth = 0;
        $prevMonth = 0;

        foreach ($allPayment as $item) {
            $total += $item->amount;

            if (date('m', strtotime($item->created_at)) == date('m')) {
                $currentMonth += $item->amount;
            }

            if (date('m', strtotime($item->created_at)) == (date('m') - 1 < 1 ? $prev = 12 : $prev = date('m') - 1)) {
                $prevMonth += $item->amount;
            }
        }

        return view('admin.shop.payments', [
            'payments' => $payments,
            'total' => $total,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'count' => count($allPayment),
            'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function viewInvoice(Request $request, $id)
    {
        $payment = DB::table('payments')->where('id', '=', (int) $id)->where('completed', '=', 1)->get();
        if (count($payment) < 1) {
            throw new NotFoundHttpException('3030');
        }

        return response()->file(storage_path(sprintf('app/invoices/%s', $payment[0]->invoice_number)));
    }
}
