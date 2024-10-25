<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function count;
use function in_array;

final class DropTableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        DatabaseInterface $dbi,
        RelationCleanup $relationCleanup,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
        $this->relationCleanup = $relationCleanup;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $db, $message, $reload, $sql_query;

        $reload = $_POST['reload'] ?? $reload ?? null;
        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $views = $this->dbi->getVirtualTables($db);

        if ($multBtn !== __('Yes')) {
            $message = Message::success(__('No change'));

            if (empty($_POST['message'])) {
                $_POST['message'] = Message::success();
            }

            unset($_POST['mult_btn']);

            ($this->structureController)();

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
        $sql_query = '';
        $sqlQueryViews = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $this->relationCleanup->table($db, $selected[$i]);
            $current = $selected[$i];

            if (! empty($views) && in_array($current, $views)) {
                $sqlQueryViews .= (empty($sqlQueryViews) ? 'DROP VIEW ' : ', ') . Util::backquote($current);
            } else {
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ') . Util::backquote($current);
            }

            $reload = 1;
        }

        if (! empty($sql_query)) {
            $sql_query .= ';';
        } elseif (! empty($sqlQueryViews)) {
            $sql_query = $sqlQueryViews . ';';
            unset($sqlQueryViews);
        }

        // Unset cache values for tables count, issue #14205
        if (isset($_SESSION['tmpval'])) {
            if (isset($_SESSION['tmpval']['table_limit_offset'])) {
                unset($_SESSION['tmpval']['table_limit_offset']);
            }

            if (isset($_SESSION['tmpval']['table_limit_offset_db'])) {
                unset($_SESSION['tmpval']['table_limit_offset_db']);
            }
        }

        $message = Message::success();

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if (! $result) {
            $message = Message::error($this->dbi->getError());
        }

        if ($result && ! empty($sqlQueryViews)) {
            $sql_query .= ' ' . $sqlQueryViews . ';';
            $result = $this->dbi->tryQuery($sqlQueryViews);
            unset($sqlQueryViews);
        }

        if (! $result) {
            $message = Message::error($this->dbi->getError());
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        unset($_POST['mult_btn']);

        ($this->structureController)();
    }
}
