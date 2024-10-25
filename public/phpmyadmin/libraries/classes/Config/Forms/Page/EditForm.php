<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\FeaturesForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class EditForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Edit' => MainForm::getForms()['Edit'],
            'Text_fields' => FeaturesForm::getForms()['Text_fields'],
        ];
    }
}
