<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Illuminate\Database\Migrations\Migration;

class MigrateToNewServiceSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $service = Service::where('author', config('pterodactyl.service.core'))->where('folder', 'srcds')->first();
            if (! $service) {
                return;
            }

            $options = ServiceOption::where('service_id', $service->id)->get();
            $options->each(function ($item) use ($options) {
                if ($item->tag === 'srcds' && $item->name === 'Insurgency') {
                    $item->tag = 'insurgency';
                } elseif ($item->tag === 'srcds' && $item->name === 'Team Fortress 2') {
                    $item->tag = 'tf2';
                } elseif ($item->tag === 'srcds' && $item->name === 'Custom Source Engine Game') {
                    $item->tag = 'source';
                }
                $item->save();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Not doing reversals right now...
    }
}
