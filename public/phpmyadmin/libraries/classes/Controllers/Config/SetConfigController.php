<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function json_decode;

final class SetConfigController extends AbstractController
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
        /** @var string|null $value */
        $value = $request->getParsedBodyParam('value');

        if (! isset($key, $value)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $result = $this->config->setUserValue(null, $key, json_decode($value));

        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
