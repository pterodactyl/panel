<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

class MainForm extends \PhpMyAdmin\Config\Forms\User\MainForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        $result = parent::getForms();
        /* Following are not available to user */
        $result['Startup'][] = 'ShowPhpInfo';
        $result['Startup'][] = 'ShowChgPassword';

        return $result;
    }
}
