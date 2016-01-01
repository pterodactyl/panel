<?php

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
