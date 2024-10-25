<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function count;

final class AddPrefixTableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $db, $message, $sql_query;

        $selected = $_POST['selected'] ?? [];

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $newTableName = $_POST['add_prefix'] . $selected[$i];
            $aQuery = 'ALTER TABLE ' . Util::backquote($selected[$i])
                . ' RENAME ' . Util::backquote($newTableName);

            $sql_query .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($db);
            $this->dbi->query($aQuery);
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        ($this->structureController)();
    }
}
