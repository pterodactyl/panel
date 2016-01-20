<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Repositories;

class HelperRepository {

    /**
     * Listing of editable files in the control panel.
     * @var array
     */
    protected static $editable = [
        'txt',
        'yml',
        'yaml',
        'log',
        'conf',
        'config',
        'html',
        'json',
        'properties',
        'props',
        'cfg',
        'lang',
        'ini',
        'cmd',
        'sh',
        'lua',
        '0' // Supports BungeeCord Files
    ];

    public function __construct()
    {
        //
    }

    /**
     * Converts from bytes to the largest possible size that is still readable.
     *
     * @param  int $bytes
     * @param  int $decimals
     * @return string
     */
    public static function bytesToHuman($bytes, $decimals = 2)
    {

        $sz = explode(',', 'B,KB,MB,GB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.$sz[$factor];

    }

    public static function editableFiles()
    {
        return self::$editable;
    }

}
