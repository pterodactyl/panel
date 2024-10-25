<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure\CentralColumns;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class AddController extends AbstractController
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
        global $message;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->syncUniqueColumns($selected);

        $message = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        ($this->structureController)();
    }
}
