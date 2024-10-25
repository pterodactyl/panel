<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class GetConfigController extends AbstractController
{
    /** @var Config */
    private $config;

    public function __construct(ResponseRenderer $response, Template $template, Config $config)
    {
        parent::__construct($response, $template);
        $this->config = $config;
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string|null $key */
        $key = $request->getParsedBodyParam('key');

        if (! isset($key)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['value' => $this->config->get($key)]);
    }
}
