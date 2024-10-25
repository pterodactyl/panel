<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Setup\FormProcessing;

use function in_array;
use function is_numeric;
use function is_string;
use function ob_get_clean;
use function ob_start;

class ServersController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        $formset = isset($params['formset']) && is_string($params['formset']) ? $params['formset'] : '';
        $id = isset($params['id']) && is_numeric($params['id']) && (int) $params['id'] >= 1 ? (int) $params['id'] : 0;
        $mode = '';
        if (isset($params['mode']) && in_array($params['mode'], ['add', 'edit', 'revert'], true)) {
            $mode = $params['mode'];
        }

        $pages = $this->getPages();

        $hasServer = $id >= 1 && $this->config->get('Servers/' . $id) !== null;

        if (! $hasServer && $mode !== 'revert' && $mode !== 'edit') {
            $id = 0;
        }

        ob_start();
        FormProcessing::process(new ServersForm($this->config, $id));
        $page = ob_get_clean();

        return $this->template->render('setup/servers/index', [
            'formset' => $formset,
            'pages' => $pages,
            'has_server' => $hasServer,
            'mode' => $mode,
            'server_id' => $id,
            'server_dsn' => $this->config->getServerDSN($id),
            'page' => $page,
        ]);
    }

    /**
     * @param array $params Request parameters
     */
    public function destroy(array $params): void
    {
        $id = isset($params['id']) && is_numeric($params['id']) && (int) $params['id'] >= 1 ? (int) $params['id'] : 0;

        $hasServer = $id >= 1 && $this->config->get('Servers/' . $id) !== null;

        if (! $hasServer) {
            return;
        }

        $this->config->removeServer($id);
    }
}
