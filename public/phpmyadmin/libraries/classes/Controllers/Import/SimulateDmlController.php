<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;

use function __;
use function count;
use function explode;

final class SimulateDmlController extends AbstractController
{
    /** @var SimulateDml */
    private $simulateDml;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        SimulateDml $simulateDml
    ) {
        parent::__construct($response, $template);
        $this->simulateDml = $simulateDml;
    }

    public function __invoke(): void
    {
        $error = '';
        $errorMsg = __('Only single-table UPDATE and DELETE queries can be simulated.');
        /** @var string $sqlDelimiter */
        $sqlDelimiter = $_POST['sql_delimiter'];
        $sqlData = [];
        /** @var string[] $queries */
        $queries = explode($sqlDelimiter, $GLOBALS['sql_query']);
        foreach ($queries as $sqlQuery) {
            if (empty($sqlQuery)) {
                continue;
            }

            // Parsing the query.
            $parser = new Parser($sqlQuery);

            if (empty($parser->statements[0])) {
                continue;
            }

            $statement = $parser->statements[0];

            if (
                ! ($statement instanceof UpdateStatement || $statement instanceof DeleteStatement)
                || ! empty($statement->join)
            ) {
                $error = $errorMsg;
                break;
            }

            $tables = Query::getTables($statement);
            if (count($tables) > 1) {
                $error = $errorMsg;
                break;
            }

            // Get the matched rows for the query.
            $result = $this->simulateDml->getMatchedRows($sqlQuery, $parser, $statement);
            $error = $this->simulateDml->getError();

            if ($error !== '') {
                break;
            }

            $sqlData[] = $result;
        }

        if ($error) {
            $message = Message::rawError($error);
            $this->response->addJSON('message', $message);
            $this->response->addJSON('sql_data', false);

            return;
        }

        $this->response->addJSON('sql_data', $sqlData);
    }
}
