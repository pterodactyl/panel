<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

class ConfigForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Config' => [
                'DefaultLang',
                'ServerDefault',
            ],
        ];
    }
}
