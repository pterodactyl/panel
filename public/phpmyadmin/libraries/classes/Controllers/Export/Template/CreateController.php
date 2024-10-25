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

use function is_array;

final class CreateController extends AbstractController
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

        /** @var string $exportType */
        $exportType = $request->getParsedBodyParam('exportType', '');
        /** @var string $templateName */
        $templateName = $request->getParsedBodyParam('templateName', '');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');
        /** @var string|null $templateId */
        $templateId = $request->getParsedBodyParam('template_id');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'username' => $cfg['Server']['user'],
            'exportType' => $exportType,
            'name' => $templateName,
            'data' => $templateData,
        ]);
        $result = $this->model->create(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $template
        );

        if ($result !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $templates = $this->model->getAll(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $template->getUsername(),
            $template->getExportType()
        );

        $this->response->setRequestStatus(true);
        $this->response->addJSON(
            'data',
            $this->template->render('export/template_options', [
                'templates' => is_array($templates) ? $templates : [],
                'selected_template' => $templateId,
            ])
        );
    }
}
