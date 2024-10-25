<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Partition;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class TruncateController extends AbstractController
{
    /** @var Maintenance */
    private $model;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Maintenance $maintenance
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->model = $maintenance;
    }

    public function __invoke(ServerRequest $request): void
    {
        $partitionName = $request->getParsedBodyParam('partition_name');

        try {
            Assert::stringNotEmpty($partitionName);
            $database = DatabaseName::fromValue($request->getParam('db'));
            $table = TableName::fromValue($request->getParam('table'));
        } catch (InvalidArgumentException $exception) {
            $message = Message::error($exception->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        [$result, $query] = $this->model->truncate($database, $table, $partitionName);

        if ($result) {
            $message = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $query,
                'success'
            );
        } else {
            $message = Generator::getMessage(
                __('Error'),
                $query,
                'error'
            );
        }

        $this->render('table/partition/truncate', [
            'partition_name' => $partitionName,
            'message' => $message,
        ]);
    }
}
