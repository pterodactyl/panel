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

namespace Pterodactyl\Repositories;

class HelperRepository
{
    /**
     * Listing of editable files in the control panel.
     * @var array
     */
    protected static $editable = [
        'application/json',
        'application/javascript',
        'application/xml',
        'application/xhtml+xml',
        'text/xml',
        'text/css',
        'text/html',
        'text/plain',
        'text/x-perl',
        'text/x-shellscript',
        'inode/x-empty',
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

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sz[$factor];
    }

    public static function editableFiles()
    {
        return self::$editable;
    }
}
