<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Template;

use function in_array;

abstract class AbstractController
{
    /** @var ConfigFile */
    protected $config;

    /** @var Template */
    protected $template;

    public function __construct(ConfigFile $config, Template $template)
    {
        $this->config = $config;
        $this->template = $template;
    }

    /**
     * @return array
     */
    protected function getPages(): array
    {
        $ignored = [
            'Config',
            'Servers',
        ];
        $pages = [];
        foreach (SetupFormList::getAll() as $formset) {
            if (in_array($formset, $ignored)) {
                continue;
            }

            /** @var BaseForm $formClass */
            $formClass = SetupFormList::get($formset);

            $pages[$formset] = [
                'name' => $formClass::getName(),
                'formset' => $formset,
            ];
        }

        return $pages;
    }
}
