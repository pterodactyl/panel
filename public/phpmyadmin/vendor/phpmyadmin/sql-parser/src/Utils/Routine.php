<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Components\DataType;
use PhpMyAdmin\SqlParser\Components\ParameterDefinition;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function implode;
use function is_string;

/**
 * Routine utilities.
 */
class Routine
{
    /**
     * Parses a parameter of a routine.
     *
     * @param string $param parameter's definition
     *
     * @return string[]
     */
    public static function getReturnType($param)
    {
        $lexer = new Lexer($param);

        // A dummy parser is used for error reporting.
        $type = DataType::parse(new Parser(), $lexer->list);

        if ($type === null) {
            return [
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $options = [];
        foreach ($type->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        return [
            '',
            '',
            $type->name,
            implode(',', $type->parameters),
            implode(' ', $options),
        ];
    }

    /**
     * Parses a parameter of a routine.
     *
     * @param string $param parameter's definition
     *
     * @return string[]
     */
    public static function getParameter($param)
    {
        $lexer = new Lexer('(' . $param . ')');

        // A dummy parser is used for error reporting.
        $param = ParameterDefinition::parse(new Parser(), $lexer->list);

        if (empty($param[0])) {
            return [
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $param = $param[0];

        $options = [];
        foreach ($param->type->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        return [
            empty($param->inOut) ? '' : $param->inOut,
            $param->name,
            $param->type->name,
            implode(',', $param->type->parameters),
            implode(' ', $options),
        ];
    }

    /**
     * Gets the parameters of a routine from the parse tree.
     *
     * @param CreateStatement $statement the statement to be processed
     *
     * @return array<string, int|array<int, mixed[]|string|null>>
     */
    public static function getParameters($statement)
    {
        $retval = [
            'num' => 0,
            'dir' => [],
            'name' => [],
            'type' => [],
            'length' => [],
            'length_arr' => [],
            'opts' => [],
        ];

        if (! empty($statement->parameters)) {
            $idx = 0;
            foreach ($statement->parameters as $param) {
                $retval['dir'][$idx] = $param->inOut;
                $retval['name'][$idx] = $param->name;
                $retval['type'][$idx] = $param->type->name;
                $retval['length'][$idx] = implode(',', $param->type->parameters);
                $retval['length_arr'][$idx] = $param->type->parameters;
                $retval['opts'][$idx] = [];
                foreach ($param->type->options->options as $opt) {
                    $retval['opts'][$idx][] = is_string($opt) ?
                        $opt : $opt['value'];
                }

                $retval['opts'][$idx] = implode(' ', $retval['opts'][$idx]);
                ++$idx;
            }

            $retval['num'] = $idx;
        }

        return $retval;
    }
}
