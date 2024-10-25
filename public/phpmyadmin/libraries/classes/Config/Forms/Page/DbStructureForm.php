<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class DbStructureForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'DbStructure' => MainForm::getForms()['DbStructure'],
        ];
    }
}
