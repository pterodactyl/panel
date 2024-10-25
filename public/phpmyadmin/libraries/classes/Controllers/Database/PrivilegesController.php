<?php
/**
 * Controller for database privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function mb_strtolower;

/**
 * Controller for database privileges
 */
class PrivilegesController extends AbstractController
{
    /** @var Privileges */
    private $privileges;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Privileges $privileges,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->privileges = $privileges;
        $this->dbi = $dbi;
    }

    /**
     * @param string[] $params Request parameters
     * @psalm-param array{checkprivsdb: string} $params
     */
    public function __invoke(array $params): string
    {
        global $cfg, $text_dir;

        $scriptName = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');

        $db = $params['checkprivsdb'];
        if ($this->dbi->getLowerCaseNames() === '1') {
            $db = mb_strtolower($params['checkprivsdb']);
        }

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($db);
        }

        return $this->template->render('database/privileges/index', [
            'is_superuser' => $this->dbi->isSuperUser(),
            'db' => $db,
            'database_url' => $scriptName,
            'text_dir' => $text_dir,
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
    }
}
