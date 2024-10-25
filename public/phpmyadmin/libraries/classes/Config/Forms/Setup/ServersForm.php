<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

use function __;

class ServersForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        // phpcs:disable Squiz.Arrays.ArrayDeclaration.KeySpecified,Squiz.Arrays.ArrayDeclaration.NoKeySpecified
        return [
            'Server' => [
                'Servers' => [
                    1 => [
                        'verbose',
                        'host',
                        'port',
                        'socket',
                        'ssl',
                        'compress',
                    ],
                ],
            ],
            'Server_auth' => [
                'Servers' => [
                    1 => [
                        'auth_type',
                        ':group:' . __('Config authentication'),
                        'user',
                        'password',
                        ':group:end',
                        ':group:' . __('HTTP authentication'),
                        'auth_http_realm',
                        ':group:end',
                        ':group:' . __('Signon authentication'),
                        'SignonSession',
                        'SignonURL',
                        'LogoutURL',
                    ],
                ],
            ],
            'Server_config' => [
                'Servers' => [
                    1 => [
                        'only_db',
                        'hide_db',
                        'AllowRoot',
                        'AllowNoPassword',
                        'DisableIS',
                        'AllowDeny/order',
                        'AllowDeny/rules',
                        'SessionTimeZone',
                    ],
                ],
            ],
            'Server_pmadb' => [
                'Servers' => [
                    1 => [
                        'pmadb' => 'phpmyadmin',
                        'controlhost',
                        'controlport',
                        'controluser',
                        'controlpass',
                        'bookmarktable' => 'pma__bookmark',
                        'relation' => 'pma__relation',
                        'userconfig' => 'pma__userconfig',
                        'users' => 'pma__users',
                        'usergroups' => 'pma__usergroups',
                        'navigationhiding' => 'pma__navigationhiding',
                        'table_info' => 'pma__table_info',
                        'column_info' => 'pma__column_info',
                        'history' => 'pma__history',
                        'recent' => 'pma__recent',
                        'favorite' => 'pma__favorite',
                        'table_uiprefs' => 'pma__table_uiprefs',
                        'tracking' => 'pma__tracking',
                        'table_coords' => 'pma__table_coords',
                        'pdf_pages' => 'pma__pdf_pages',
                        'savedsearches' => 'pma__savedsearches',
                        'central_columns' => 'pma__central_columns',
                        'designer_settings' => 'pma__designer_settings',
                        'export_templates' => 'pma__export_templates',
                        'MaxTableUiprefs' => 100,
                    ],
                ],
            ],
            'Server_tracking' => [
                'Servers' => [
                    1 => [
                        'tracking_version_auto_create',
                        'tracking_default_statements',
                        'tracking_add_drop_view',
                        'tracking_add_drop_table',
                        'tracking_add_drop_database',
                    ],
                ],
            ],
        ];

        // phpcs:enable
    }
}
