<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\RecentFavoriteTable;

final class RecentTablesListController extends AbstractController
{
    public function __invoke(): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        $this->response->addJSON([
            'list' => RecentFavoriteTable::getInstance('recent')->getHtmlList(),
        ]);
    }
}
