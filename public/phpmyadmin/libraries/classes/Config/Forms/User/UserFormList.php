<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseFormList;

class UserFormList extends BaseFormList
{
    /** @var string[] */
    protected static $all = [
        'Features',
        'Sql',
        'Navi',
        'Main',
        'Export',
        'Import',
    ];
    /** @var string */
    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\User\\';
}
