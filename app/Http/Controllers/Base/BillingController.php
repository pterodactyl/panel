<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Validator;

class BillingController extends Controller
{
    private const COUNTRIES = array
    (
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, Democratic Republic',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island & Mcdonald Islands',
        'VA' => 'Holy See (Vatican City State)',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran, Islamic Republic Of',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle Of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KR' => 'Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia, Federated States Of',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territory, Occupied',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And Sandwich Isl.',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis And Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );

    public function index(Request $request)
    {
        return view('base.billing')
            ->with('user', $request->user())->with('countries', self::COUNTRIES)
            ->with('invoices', $request->user()->invoices()->latest()->paginate(5));
    }

    public function billing(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|min:3|max:255',
            'last_name' => 'required|string|min:3|max:255',
            'address' => 'required|string|min:3|max:255',
            'city' => 'required|string|min:3|max:255',
            'country' => 'required|string|max:2|in:'.implode(',', array_keys(self::COUNTRIES)),
            'zip' => 'required|string|min:3|max:6',
        ]);
        $user = $request->user();
        $user->billing_first_name = $request->first_name;
        $user->billing_last_name = $request->last_name;
        $user->billing_address = $request->address;
        $user->billing_city = $request->city;
        $user->billing_country = $request->country;
        $user->billing_zip = $request->zip;
        $user->save();
        return redirect()->back();
    }

    private function validateBilling($user)
    {
        if (!$user->billing_first_name) return false;
        if (!$user->billing_last_name) return false;
        if (!$user->billing_address) return false;
        if (!$user->billing_city) return false;
        if (!$user->billing_country) return false;
        if (!$user->billing_zip) return false;
        return true;
    }

    public function link(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:1000',
            'card_token' => 'required',
        ]);
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $user = $request->user();
        if (!$this->validateBilling($user)) {
            return redirect()->back()->withErrors(trans('base.errors.billing.identity'));}
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'source'  => $request->card_token
            ]);
            $charge = Charge::create([
                'customer' => $customer->id,
                'amount'   => $request->amount * 100,
                'currency' => 'usd'
            ]);
            if ($charge->paid) {
                $user->stripe_card_brand = $request->card_brand;
                $user->stripe_card_last4 = $request->card_last4;
                $user->stripe_customer_id = $customer->id;
                $user->addBalance($request->amount);
            } else {
                return redirect()->back()->withErrors(trans('base.errors.billing.failed'));}
        } catch (\Exception $ex) {}
        return redirect()->back();
    }

    public function unlink(Request $request)
    {
        $user = $request->user();
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        Customer::retrieve($user->stripe_customer_id)->delete();
        $user->stripe_customer_id = null;
        $user->stripe_card_brand = null;
        $user->stripe_card_last4 = null;
        $user->save();
        return redirect()->back();
    }

    public function invoicePdf(Request $request)
    {
        $invoice = $request->user()->invoices()->find($request->id);
        if (!$invoice) return abort(404);
        return $invoice->downloadPdf();
    }

    private function getPaypalApiContext()
    {
        return new ApiContext(
            new OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'), 
                env('PAYPAL_CLIENT_SECRET'), 
                env('PAYPAL_CLIENT_ENV')
            )
        );
    }

    public function paypal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:1000',
        ]);
        if (!$this->validateBilling($request->user())) {
            return redirect()->back()->withErrors(trans('base.errors.billing.identity'));}
        $apiContext = $this->getPaypalApiContext();
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $amount = new Amount();
        $amount->setTotal($request->amount);
        $amount->setCurrency('USD');
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('account.billing.paypal.callback'));
        $redirectUrls->setCancelUrl(route('account.billing.paypal.callback'));
        $payment = new Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setTransactions(array($transaction));
        $payment->setRedirectUrls($redirectUrls);
        try {
            $payment->create($apiContext);
            $links = array_filter($payment->links, function($link) {
                return $link->rel == 'approval_url';});
            $link = reset($links)->getHref();
            $meta[$payment->id] = $request->amount;
            session()->put('paypal_meta', $meta);
            return redirect($link);
        } catch (\Exception $ex) {}
        return redirect()->back();
    }

    public function paypalCallback(Request $request) {
        if (!$request->has('paymentId') || !session()->has("paypal_meta.$request->paymentId")) {
            return redirect()->route('account.billing')
                ->withErrors('Something went wrong during the paypal transaction!');
        }
        $user = $request->user();
        $amount = $request->session()->pull("paypal_meta.$request->paymentId");
        $apiContext = $this->getPaypalApiContext();
        $payment = Payment::get($request->paymentId, $apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);
        try {
            $result = $payment->execute($execution, $apiContext);
            if ($result->getState() == 'approved') {
                $user->addBalance($amount);}
        } catch (Exception $ex) {}
        return redirect()->route('account.billing');
    }
}
