<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\RenameOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * `RENAME` statement.
 *
 * RENAME TABLE tbl_name TO new_tbl_name
 *  [, tbl_name2 TO new_tbl_name2] ...
 */
class RenameStatement extends Statement
{
    /**
     * The old and new names of the tables.
     *
     * @var RenameOperation[]|null
     */
    public $renames;

    /**
     * Function called before the token is processed.
     *
     * Skips the `TABLE` keyword after `RENAME`.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     *
     * @return void
     */
    public function before(Parser $parser, TokensList $list, Token $token)
    {
        if (($token->type !== Token::TYPE_KEYWORD) || ($token->keyword !== 'RENAME')) {
            return;
        }

        // Checking if it is the beginning of the query.
        $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'TABLE');
    }

    /**
     * @return string
     */
    public function build()
    {
        return 'RENAME TABLE ' . RenameOperation::build($this->renames);
    }
}
