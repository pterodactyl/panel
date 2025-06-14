<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Classes\Countries;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\Account\PersonalSettingsRequest;

class PersonalSettingsController extends ClientApiController
{
    /**
     * @param PersonalSettingsRequest $request
     * @return array
     */
    public function index(PersonalSettingsRequest $request)
    {
        $countries = [];

        foreach (Countries::$countries as $key => $country) {
            array_push($countries, [
                'code' => $key,
                'name' => $country,
            ]);
        }

        return [
            'success' => true,
            'data' => [
                'countries' => $countries,
                'country' => \Auth::user()->country,
                'address' => \Auth::user()->address,
                'zipcode' => \Auth::user()->zip_code,
            ],
        ];
    }

    /**
     * @param PersonalSettingsRequest $request
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function savePersonalSettings(PersonalSettingsRequest $request)
    {
        $this->validate($request, [
            'country' => 'required',
            'address' => 'required|min:1|max:191',
            'zipcode' => 'required',
        ]);

        if (!isset(Countries::$countries[trim(strip_tags($request->input('country', '')))])) {
            throw new DisplayException('Invalid country');
        }

        DB::table('users')->where('id', '=', \Auth::user()->id)->update([
            'country' => trim(strip_tags($request->input('country', 'CZ'))),
            'address' => trim(strip_tags($request->input('address', ''))),
            'zip_code' => trim(strip_tags($request->input('zipcode', ''))),
        ]);

        return [
            'success' => true,
            'data' => [],
        ];
    }
}
