<?php
/**
 * Code for displaying server selection
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function count;
use function implode;
use function is_array;
use function str_contains;

/**
 * Displays the MySQL servers choice form
 */
class Select
{
    /**
     * Renders the server selection in list or selectbox form, or option tags only
     *
     * @param bool $not_only_options whether to include form tags or not
     * @param bool $omit_fieldset    whether to omit fieldset tag or not
     *
     * @return string
     */
    public static function render($not_only_options, $omit_fieldset)
    {
        // Show as list?
        if ($not_only_options) {
            $list = $GLOBALS['cfg']['DisplayServersList'];
            $not_only_options = ! $list;
        } else {
            $list = false;
        }

        $form_action = '';
        if ($not_only_options) {
            $form_action = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabServer'], 'server');
        }

        $servers = [];
        foreach ($GLOBALS['cfg']['Servers'] as $key => $server) {
            if (empty($server['host'])) {
                continue;
            }

            if (! empty($GLOBALS['server']) && (int) $GLOBALS['server'] === (int) $key) {
                $selected = 1;
            } else {
                $selected = 0;
            }

            if (! empty($server['verbose'])) {
                $label = $server['verbose'];
            } else {
                $label = $server['host'];
                if (! empty($server['port'])) {
                    $label .= ':' . $server['port'];
                }
            }

            if (! empty($server['only_db'])) {
                if (! is_array($server['only_db'])) {
                    $label .= ' - ' . $server['only_db'];
                    // try to avoid displaying a too wide selector
                } elseif (count($server['only_db']) < 4) {
                    $label .= ' - ' . implode(', ', $server['only_db']);
                }
            }

            if (! empty($server['user']) && $server['auth_type'] === 'config') {
                $label .= '  (' . $server['user'] . ')';
            }

            if ($list) {
                if ($selected) {
                    $servers['list'][] = [
                        'selected' => true,
                        'label' => $label,
                    ];
                } else {
                    $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabServer'], 'server');
                    $href = $scriptName . Url::getCommon(
                        ['server' => $key],
                        ! str_contains($scriptName, '?') ? '?' : '&'
                    );
                    $servers['list'][] = [
                        'href' => $href,
                        'label' => $label,
                    ];
                }
            } else {
                $servers['select'][] = [
                    'value' => $key,
                    'selected' => $selected,
                    'label' => $label,
                ];
            }
        }

        $renderDetails = [
            'not_only_options' => $not_only_options,
            'omit_fieldset' => $omit_fieldset,
            'servers' => $servers,
        ];
        if ($not_only_options) {
            $renderDetails['form_action'] = $form_action;
        }

        $template = new Template();

        return $template->render('server/select/index', $renderDetails);
    }
}
