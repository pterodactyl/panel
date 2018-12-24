<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Invoice;
use Pterodactyl\Models\User;
use DB;

class BillingController extends Controller
{
    public function index(Request $req)
    {
        $this_month_income = Invoice::where(DB::raw('MONTH(created_at) = MONTH(NOW())'))->sum('amount');
        $this_year_income = Invoice::where(DB::raw('YEAR(created_at) = YEAR(NOW())'))->sum('amount');
        $income_month_graph = Invoice::select(DB::raw('SUM(amount) AS amount, MONTH(created_at) AS month, CONCAT(\'#\', LEFT(MD5(MONTH(created_at)), 6)) AS color'))
            ->where(DB::raw('YEAR(NOW()) = YEAR(created_at)'))->groupBy(DB::raw('MONTH(created_at)'))->get();
        $income_country_graph = Invoice::select(DB::raw('SUM(amount) AS amount, billing_country, CONCAT(\'#\', LEFT(MD5(billing_country), 6)) AS color'))
            ->where(DB::raw('YEAR(NOW()) = YEAR(created_at)'))->groupBy('billing_country')->get();
        return view('admin.billing.index')
            ->with('invoices', Invoice::latest()->paginate(25))
            ->with('this_month_income', $this_month_income)
            ->with('this_year_income', $this_year_income)
            ->with('income_month_graph', $income_month_graph)
            ->with('income_country_graph', $income_country_graph);
    }

    public function new(Request $req)
    {
        return view('admin.billing.new');
    }

    public function submit(Request $req) 
    {
        $req->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:-500|max:500',
        ]);
        $user = User::find($req->user_id);
        $user->addBalance($req->amount);
        return redirect()->back();
    }

    public function pdf(Request $req) 
    {
        return Invoice::find($req->id)->downloadPdf();
    }
}
