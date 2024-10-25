<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;

class ThemesController extends AbstractController
{
    /** @var ThemeManager */
    private $themeManager;

    public function __construct(ResponseRenderer $response, Template $template, ThemeManager $themeManager)
    {
        parent::__construct($response, $template);
        $this->themeManager = $themeManager;
    }

    public function __invoke(): void
    {
        $themes = $this->themeManager->getThemesArray();
        $themesList = $this->template->render('home/themes', ['themes' => $themes]);
        $this->response->setAjax(true);
        $this->response->addJSON('themes', $themesList);
    }
}
