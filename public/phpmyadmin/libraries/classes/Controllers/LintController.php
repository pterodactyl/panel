<?php
/**
 * Represents the interface between the linter and the query editor.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Linter;

use function is_array;
use function is_string;
use function json_encode;

/**
 * Represents the interface between the linter and the query editor.
 */
class LintController extends AbstractController
{
    public const EDITOR_SQL_PREFIX = [
        'event' => "DELIMITER $$ CREATE EVENT `a` ON SCHEDULE EVERY MINUTE DO\n",
        'routine' => "DELIMITER $$ CREATE PROCEDURE `a`()\n",
        'trigger' => "DELIMITER $$ CREATE TRIGGER `a` AFTER INSERT ON `b` FOR EACH ROW\n",
    ];

    public function __invoke(): void
    {
        $sqlQueryParam = $_POST['sql_query'] ?? null;
        $options = $_POST['options'] ?? null;

        /**
         * The SQL query to be analyzed.
         *
         * This does not need to be checked against XSS or MySQL injections because it is
         * never executed, just parsed.
         *
         * The client, which will receive the JSON response will decode the message and
         * and any HTML fragments that are displayed to the user will be encoded anyway.
         */
        $sqlQuery = is_string($sqlQueryParam) ? $sqlQueryParam : '';

        $editorType = is_array($options) ? ($options['editorType'] ?? null) : null;
        $prefix = is_string($editorType) ? self::EDITOR_SQL_PREFIX[$editorType] ?? '' : '';

        $lints = Linter::lint($prefix . $sqlQuery);
        if ($prefix !== '') {
            // Adjust positions to account for prefix
            foreach ($lints as $i => $lint) {
                if ($lint['fromLine'] === 0) {
                    continue;
                }

                $lints[$i]['fromLine'] -= 1;
                $lints[$i]['toLine'] -= 1;
            }
        }

        $this->response->setAjax(true);
        // Disabling standard response.
        $this->response->disable();
        Core::headerJSON();
        echo json_encode($lints);
    }
}
