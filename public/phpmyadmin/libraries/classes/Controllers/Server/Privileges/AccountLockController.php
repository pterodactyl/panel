<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Privileges;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Template;
use Throwable;

use function __;

final class AccountLockController extends AbstractController
{
    /** @var AccountLocking */
    private $model;

    public function __construct(ResponseRenderer $response, Template $template, AccountLocking $accountLocking)
    {
        parent::__construct($response, $template);
        $this->model = $accountLocking;
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->response->setAjax(true);

        /** @var string $userName */
        $userName = $request->getParsedBodyParam('username');
        /** @var string $hostName */
        $hostName = $request->getParsedBodyParam('hostname');

        try {
            $this->model->lock($userName, $hostName);
        } catch (Throwable $exception) {
            $this->response->setHttpResponseCode(400);
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error($exception->getMessage())]);

            return;
        }

        $message = Message::success(__('The account %s@%s has been successfully locked.'));
        $message->addParam($userName);
        $message->addParam($hostName);
        $this->response->addJSON(['message' => $message]);
    }
}
