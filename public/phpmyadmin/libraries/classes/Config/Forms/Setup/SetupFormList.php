<?php
/**
 * Setup preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseFormList;

class SetupFormList extends BaseFormList
{
    /** @var string[] */
    protected static $all = [
        'Config',
        'Export',
        'Features',
        'Import',
        'Main',
        'Navi',
        'Servers',
        'Sql',
    ];
    /** @var string */
    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\Setup\\';
}
