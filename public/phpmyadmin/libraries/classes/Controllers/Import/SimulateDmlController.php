<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;

use function __;
use function array_filter;
use function array_values;
use function count;

final class SimulateDmlController extends AbstractController
{
    /** @var SimulateDml */
    private $simulateDml;

    /** @var string */
    private $error = '';
    /**
     * @var list<array<mixed>>
     * @psalm-var list<array{
     *   sql_query: string,
     *   matched_rows: int,
     *   matched_rows_url: string,
     * }>
     */
    private $data = [];

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
        /** @var string $sqlDelimiter */
        $sqlDelimiter = $_POST['sql_delimiter'];

        $parser = $this->createParser($GLOBALS['sql_query'], $sqlDelimiter);
        $this->process($parser);

        if ($this->error) {
            $this->response->addJSON('message', Message::rawError($this->error));
            $this->response->addJSON('sql_data', false);

            return;
        }

        $this->response->addJSON('sql_data', $this->data);
    }

    private function createParser(string $query, string $delimiter): Parser
    {
        $lexer = new Lexer($query, false, $delimiter);
        $list = new TokensList(array_values(array_filter(
            $lexer->list->tokens,
            static function ($token): bool {
                return $token->type !== Token::TYPE_COMMENT;
            }
        )));

        return new Parser($list);
    }

    private function process(Parser $parser): void
    {
        foreach ($parser->statements as $statement) {
            if (
                ! $statement instanceof UpdateStatement && ! $statement instanceof DeleteStatement
                || ! empty($statement->join)
                || count(Query::getTables($statement)) > 1
            ) {
                $this->error = __('Only single-table UPDATE and DELETE queries can be simulated.');
                break;
            }

            // Get the matched rows for the query.
            $result = $this->simulateDml->getMatchedRows($parser, $statement);
            $this->error = $this->simulateDml->getError();

            if ($this->error !== '') {
                break;
            }

            $this->data[] = $result;
        }
    }
}
