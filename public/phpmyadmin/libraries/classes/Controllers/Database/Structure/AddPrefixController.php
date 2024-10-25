<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\AbstractController;

use function __;

final class AddPrefixController extends AbstractController
{
    public function __invoke(): void
    {
        global $db;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $params = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $params['selected'][] = $selectedValue;
        }

        $this->response->disable();
        $this->render('database/structure/add_prefix', ['url_params' => $params]);
    }
}
