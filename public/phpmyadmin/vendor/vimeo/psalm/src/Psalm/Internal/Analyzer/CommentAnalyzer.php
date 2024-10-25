<?php

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Scanner\DocblockParser;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Internal\Scanner\VarDocblockComment;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type\Union;

use function array_merge;
use function count;
use function preg_match;
use function preg_replace;
use function preg_split;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;
use function substr_count;
use function trim;

/**
 * @internal
 */
class CommentAnalyzer
{
    public const TYPE_REGEX = '(\??\\\?[\(\)A-Za-z0-9_&\<\.=,\>\[\]\-\{\}:|?\\\\]*|\$[a-zA-Z_0-9_]+)';

    /**
     * @param  array<string, array<string, Union>>|null   $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return list<VarDocblockComment>
     */
    public static function getTypeFromComment(
        PhpParser\Comment\Doc $comment,
        FileSource $source,
        Aliases $aliases,
        ?array $template_type_map = null,
        ?array $type_aliases = null
    ): array {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        return self::arrayToDocblocks(
            $comment,
            $parsed_docblock,
            $source,
            $aliases,
            $template_type_map,
            $type_aliases
        );
    }

    /**
     * @param  array<string, array<string, Union>>|null   $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @return list<VarDocblockComment>
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function arrayToDocblocks(
        PhpParser\Comment\Doc $comment,
        ParsedDocblock $parsed_docblock,
        FileSource $source,
        Aliases $aliases,
        ?array $template_type_map = null,
        ?array $type_aliases = null
    ): array {
        $var_id = null;

        $var_type_tokens = null;
        $original_type = null;

        $var_comments = [];

        $comment_text = $comment->getText();

        $var_line_number = $comment->getStartLine();

        if (isset($parsed_docblock->combined_tags['var'])) {
            foreach ($parsed_docblock->combined_tags['var'] as $offset => $var_line) {
                $var_line = trim($var_line);

                if (!$var_line) {
                    continue;
                }

                $type_start = null;
                $type_end = null;

                $line_parts = self::splitDocLine($var_line);

                $line_number = $comment->getStartLine() + substr_count(
                    $comment_text,
                    "\n",
                    0,
                    $offset - $comment->getStartFilePos()
                );
                $description = $parsed_docblock->description;

                if ($line_parts[0]) {
                    $type_start = $offset;
                    $type_end = $type_start + strlen($line_parts[0]);

                    $line_parts[0] = self::sanitizeDocblockType($line_parts[0]);

                    if ($line_parts[0] === ''
                        || ($line_parts[0][0] === '$'
                            && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                    ) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    try {
                        $var_type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                            $line_parts[0],
                            $aliases,
                            $template_type_map,
                            $type_aliases
                        );
                    } catch (TypeParseTreeException $e) {
                        throw new DocblockParseException($line_parts[0] . ' is not a valid type');
                    }

                    $original_type = $line_parts[0];

                    $var_line_number = $line_number;

                    if (count($line_parts) > 1) {
                        if ($line_parts[1][0] === '$') {
                            $var_id = $line_parts[1];
                            $description = trim(substr($var_line, strlen($line_parts[0]) + strlen($line_parts[1]) + 2));
                        } else {
                            $description = trim(substr($var_line, strlen($line_parts[0]) + 1));
                        }
                        $description = preg_replace('/\\n \\*\\s+/um', ' ', $description);
                    }
                }

                if (!$var_type_tokens || !$original_type) {
                    continue;
                }

                try {
                    $defined_type = TypeParser::parseTokens(
                        $var_type_tokens,
                        null,
                        $template_type_map ?: [],
                        $type_aliases ?: []
                    );
                } catch (TypeParseTreeException $e) {
                    throw new DocblockParseException(
                        $line_parts[0] .
                        ' is not a valid type' .
                        ' (from ' .
                        $source->getFilePath() .
                        ':' .
                        $comment->getStartLine() .
                        ')'
                    );
                }

                $defined_type->setFromDocblock();

                $var_comment = new VarDocblockComment();
                $var_comment->type = $defined_type;
                $var_comment->var_id = $var_id;
                $var_comment->line_number = $var_line_number;
                $var_comment->type_start = $type_start;
                $var_comment->type_end = $type_end;
                $var_comment->description = $description;

                self::decorateVarDocblockComment($var_comment, $parsed_docblock);

                $var_comments[] = $var_comment;
            }
        }

        if (!$var_comments
            && (isset($parsed_docblock->tags['deprecated'])
                || isset($parsed_docblock->tags['internal'])
                || isset($parsed_docblock->tags['readonly'])
                || isset($parsed_docblock->tags['psalm-readonly'])
                || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation'])
                || isset($parsed_docblock->tags['psalm-allow-private-mutation'])
                || isset($parsed_docblock->tags['psalm-taint-escape'])
                || isset($parsed_docblock->tags['psalm-internal'])
                || isset($parsed_docblock->tags['psalm-suppress'])
                || $parsed_docblock->description)
        ) {
            $var_comment = new VarDocblockComment();

            self::decorateVarDocblockComment($var_comment, $parsed_docblock);

            $var_comments[] = $var_comment;
        }

        return $var_comments;
    }

    private static function decorateVarDocblockComment(
        VarDocblockComment $var_comment,
        ParsedDocblock $parsed_docblock
    ): void {
        $var_comment->deprecated = isset($parsed_docblock->tags['deprecated']);
        $var_comment->internal = isset($parsed_docblock->tags['internal']);
        $var_comment->readonly = isset($parsed_docblock->tags['readonly'])
            || isset($parsed_docblock->tags['psalm-readonly'])
            || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation']);

        $var_comment->allow_private_mutation
            = isset($parsed_docblock->tags['psalm-allow-private-mutation'])
            || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation']);

        if (!$var_comment->description) {
            $var_comment->description = $parsed_docblock->description;
        }

        if (isset($parsed_docblock->tags['psalm-taint-escape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-escape'] as $param) {
                $param = trim($param);
                $var_comment->removed_taints[] = $param;
            }
        }

        if (count($var_comment->psalm_internal = DocblockParser::handlePsalmInternal($parsed_docblock)) !== 0) {
            $var_comment->internal = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $var_comment->suppressed_issues[$issue_offset + $offset] = $suppressed_issue;
                }
            }
        }
    }

    /**
     * @psalm-pure
     */
    public static function sanitizeDocblockType(string $docblock_type): string
    {
        $docblock_type = preg_replace('@^[ \t]*\*@m', '', $docblock_type);
        $docblock_type = preg_replace('/,\n\s+\}/', '}', $docblock_type);
        return str_replace("\n", '', $docblock_type);
    }

    /**
     * @throws DocblockParseException if an invalid string is found
     *
     * @return non-empty-list<string>
     *
     * @psalm-pure
     */
    public static function splitDocLine(string $return_block): array
    {
        $brackets = '';

        $type = '';

        $expects_callable_return = false;

        $return_block = str_replace("\t", ' ', $return_block);

        $quote_char = null;
        $escaped = false;

        for ($i = 0, $l = strlen($return_block); $i < $l; ++$i) {
            $char = $return_block[$i];
            $next_char = $i < $l - 1 ? $return_block[$i + 1] : null;
            $last_char = $i > 0 ? $return_block[$i - 1] : null;

            if ($quote_char) {
                if ($char === $quote_char && !$escaped) {
                    $quote_char = null;

                    $type .= $char;

                    continue;
                }

                if ($char === '\\' && !$escaped && ($next_char === $quote_char || $next_char === '\\')) {
                    $escaped = true;

                    $type .= $char;

                    continue;
                }

                $escaped = false;

                $type .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                $quote_char = $char;

                $type .= $char;

                continue;
            }

            if ($char === ':' && $last_char === ')') {
                $expects_callable_return = true;

                $type .= $char;

                continue;
            }

            if ($char === '[' || $char === '{' || $char === '(' || $char === '<') {
                $brackets .= $char;
            } elseif ($char === ']' || $char === '}' || $char === ')' || $char === '>') {
                $last_bracket = substr($brackets, -1);
                $brackets = substr($brackets, 0, -1);

                if (($char === ']' && $last_bracket !== '[')
                    || ($char === '}' && $last_bracket !== '{')
                    || ($char === ')' && $last_bracket !== '(')
                    || ($char === '>' && $last_bracket !== '<')
                ) {
                    throw new DocblockParseException('Invalid string ' . $return_block);
                }
            } elseif ($char === ' ') {
                if ($brackets) {
                    $expects_callable_return = false;
                    $type .= ' ';
                    continue;
                }

                if ($next_char === '|' || $next_char === '&') {
                    $nexter_char = $i < $l - 2 ? $return_block[$i + 2] : null;

                    if ($nexter_char === ' ') {
                        ++$i;
                        $type .= $next_char . ' ';
                        continue;
                    }
                }

                if ($last_char === '|' || $last_char === '&') {
                    $type .= ' ';
                    continue;
                }

                if ($next_char === ':') {
                    ++$i;
                    $type .= ' :';
                    $expects_callable_return = true;
                    continue;
                }

                if ($expects_callable_return) {
                    $type .= ' ';
                    $expects_callable_return = false;
                    continue;
                }

                $remaining = trim(preg_replace('@^[ \t]*\* *@m', ' ', substr($return_block, $i + 1)));

                if ($remaining) {
                    return array_merge([rtrim($type)], preg_split('/[ \s]+/', $remaining));
                }

                return [$type];
            }

            $expects_callable_return = false;

            $type .= $char;
        }

        return [$type];
    }
}
