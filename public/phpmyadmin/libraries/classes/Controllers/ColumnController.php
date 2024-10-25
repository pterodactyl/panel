<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class ColumnController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string|null $db */
        $db = $request->getParsedBodyParam('db');
        /** @var string|null $table */
        $table = $request->getParsedBodyParam('table');

        if (! isset($db, $table)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['columns' => $this->dbi->getColumnNames($db, $table)]);
    }
}
