<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Setup\ConfigGenerator;

use function is_string;

class ConfigController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function __invoke(array $params): string
    {
        $formset = isset($params['formset']) && is_string($params['formset']) ? $params['formset'] : '';
        $eol = isset($params['eol']) && $params['eol'] === 'win' ? 'win' : 'unix';

        $pages = $this->getPages();

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        $config = ConfigGenerator::getConfigFile($this->config);

        return $this->template->render('setup/config/index', [
            'formset' => $formset,
            'pages' => $pages,
            'eol' => $eol,
            'config' => $config,
            'has_check_page_refresh' => $hasCheckPageRefresh,
        ]);
    }
}
