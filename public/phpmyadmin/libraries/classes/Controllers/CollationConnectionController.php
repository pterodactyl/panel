<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

final class CollationConnectionController extends AbstractController
{
    /** @var Config */
    private $config;

    public function __construct(ResponseRenderer $response, Template $template, Config $config)
    {
        parent::__construct($response, $template);
        $this->config = $config;
    }

    public function __invoke(): void
    {
        $this->config->setUserValue(
            null,
            'DefaultConnectionCollation',
            $_POST['collation_connection'],
            'utf8mb4_unicode_ci'
        );

        $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
    }
}
