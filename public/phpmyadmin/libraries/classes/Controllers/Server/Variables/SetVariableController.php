<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Variables;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function implode;
use function is_numeric;
use function mb_strtolower;
use function preg_match;
use function trim;

final class SetVariableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     *
     * @param array $vars Request parameters
     */
    public function __invoke(ServerRequest $request, array $vars): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        $value = (string) $request->getParsedBodyParam('varValue');
        $variableName = (string) $vars['name'];
        $matches = [];
        $variableType = ServerVariablesProvider::getImplementation()->getVariableType($variableName);

        if (
            $variableType === 'byte' && preg_match(
                '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
                $value,
                $matches
            )
        ) {
            $exp = [
                'kb' => 1,
                'kib' => 1,
                'mb' => 2,
                'mib' => 2,
                'gb' => 3,
                'gib' => 3,
            ];
            $value = (float) $matches[1] * 1024 ** $exp[mb_strtolower($matches[3])];
        } else {
            $value = $this->dbi->escapeString($value);
        }

        if (! is_numeric($value)) {
            $value = "'" . $value . "'";
        }

        $json = [];
        if (! preg_match('/[^a-zA-Z0-9_]+/', $variableName)) {
            $this->dbi->query('SET GLOBAL ' . $variableName . ' = ' . $value);
            // Some values are rounded down etc.
            $varValue = $this->dbi->fetchSingleRow(
                'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                . $this->dbi->escapeString($variableName)
                . '";',
                DatabaseInterface::FETCH_NUM
            );
            [$formattedValue, $isHtmlFormatted] = $this->formatVariable($variableName, $varValue[1]);

            if ($isHtmlFormatted === false) {
                $json['variable'] = htmlspecialchars($formattedValue);
            } else {
                $json['variable'] = $formattedValue;
            }
        } else {
            $this->response->setRequestStatus(false);
            $json['error'] = __('Setting variable failed');
        }

        $this->response->addJSON($json);
    }

    /**
     * Format Variable
     *
     * @param string     $name  variable name
     * @param int|string $value variable value
     *
     * @return array formatted string and bool if string is HTML formatted
     */
    private function formatVariable($name, $value): array
    {
        $isHtmlFormatted = false;
        $formattedValue = $value;

        if (is_numeric($value)) {
            $variableType = ServerVariablesProvider::getImplementation()->getVariableType($name);

            if ($variableType === 'byte') {
                $isHtmlFormatted = true;
                /** @var string[] $bytes */
                $bytes = Util::formatByteDown($value, 3, 3);
                $formattedValue = trim(
                    $this->template->render(
                        'server/variables/format_variable',
                        [
                            'valueTitle' => Util::formatNumber($value, 0),
                            'value' => implode(' ', $bytes),
                        ]
                    )
                );
            } else {
                $formattedValue = Util::formatNumber($value, 0);
            }
        }

        return [
            $formattedValue,
            $isHtmlFormatted,
        ];
    }
}
