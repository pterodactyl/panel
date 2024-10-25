<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * Handles viewing character sets and collations
 */
class CollationsController extends AbstractController
{
    /** @var array<string, Charset> */
    private $charsets;

    /** @var array<string, array<string, Collation>> */
    private $collations;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param array<string, Charset>|null                  $charsets
     * @param array<string, array<string, Collation>>|null $collations
     */
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        ?array $charsets = null,
        ?array $collations = null
    ) {
        global $cfg;

        parent::__construct($response, $template);
        $this->dbi = $dbi;

        $this->charsets = $charsets ?? Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
        $this->collations = $collations ?? Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
    }

    public function __invoke(): void
    {
        global $errorUrl;

        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $charsets = [];
        foreach ($this->charsets as $charset) {
            $charsetCollations = [];
            foreach ($this->collations[$charset->getName()] as $collation) {
                $charsetCollations[] = [
                    'name' => $collation->getName(),
                    'description' => $collation->getDescription(),
                    'is_default' => $collation->isDefault(),
                ];
            }

            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $charsetCollations,
            ];
        }

        $this->render('server/collations/index', ['charsets' => $charsets]);
    }
}
