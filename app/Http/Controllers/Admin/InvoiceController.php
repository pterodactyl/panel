<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Throwable;
use ZipArchive;

class InvoiceController extends Controller
{
    public function downloadAllInvoices()
    {
        $zip = new ZipArchive;
        $zip_safe_path = storage_path('invoices.zip');
        $res = $zip->open($zip_safe_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $result = $this::rglob(storage_path('app/invoice/*'));
        if ($res === true) {
            $zip->addFromString('1. Info.txt', __('Created at').' '.now()->format('d.m.Y'));
            foreach ($result as $file) {
                if (file_exists($file) && is_file($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();
        }

        return response()->download($zip_safe_path);
    }

    /**
     * @param $pattern
     * @param $flags
     * @return array|false
     */
    public function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this::rglob($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    /**
     * @param $paymentID
     * @param $date
     */
    public function downloadSingleInvoice(Request $request)
    {
        $id = $request->id;
        try {
            $query = Invoice::where('payment_id', '=', $id)->firstOrFail();
        } catch (Throwable $e) {
            return redirect()->back()->with('error', __('Error!'));
        }

        $invoice_path = storage_path('app/invoice/'.$query->invoice_user.'/'.$query->created_at->format('Y').'/'.$query->invoice_name.'.pdf');

        if (! file_exists($invoice_path)) {
            return redirect()->back()->with('error', __('Invoice does not exist on filesystem!'));
        }

        return response()->download($invoice_path);
    }
}
