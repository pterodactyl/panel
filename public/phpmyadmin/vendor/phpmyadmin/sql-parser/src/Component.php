<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Exception;
use Stringable;

/**
 * Defines a component that is later extended to parse specialized components or keywords.
 *
 * There is a small difference between *Component and *Keyword classes: usually, *Component parsers can be reused in
 * multiple situations and *Keyword parsers count on the *Component classes to do their job.
 *
 * A component (of a statement) is a part of a statement that is common to multiple query types.
 */
abstract class Component implements Stringable
{
    /**
     * Parses the tokens contained in the given list in the context of the given
     * parser.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return mixed
     *
     * @throws Exception not implemented yet.
     */
    public static function parse(
        Parser $parser,
        TokensList $list,
        array $options = []
    ) {
        // This method should be abstract, but it can't be both static and
        // abstract.
        throw new Exception(Translator::gettext('Not implemented yet.'));
    }

    /**
     * Builds the string representation of a component of this type.
     *
     * In other words, this function represents the inverse function of
     * `static::parse`.
     *
     * @param mixed                $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return mixed
     *
     * @throws Exception not implemented yet.
     */
    public static function build($component, array $options = [])
    {
        // This method should be abstract, but it can't be both static and
        // abstract.
        throw new Exception(Translator::gettext('Not implemented yet.'));
    }

    /**
     * Builds the string representation of a component of this type.
     *
     * @see static::build
     *
     * @return string
     */
    public function __toString()
    {
        return static::build($this);
    }
}
