<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class LoadController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        TemplateModel $model,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->model = $model;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return;
        }

        $template = $this->model->load(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $cfg['Server']['user'],
            $templateId
        );

        if (! $template instanceof ExportTemplate) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $template);

            return;
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('data', $template->getData());
    }
}
