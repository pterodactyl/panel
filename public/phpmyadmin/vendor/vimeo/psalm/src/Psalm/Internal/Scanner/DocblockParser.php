<?php

namespace Psalm\Internal\Scanner;

use Psalm\Exception\DocblockParseException;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function count;
use function explode;
use function implode;
use function is_string;
use function min;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function strspn;
use function substr;
use function trim;

use const PREG_OFFSET_CAPTURE;

/**
 * This class will parse Docblocks in order to extract known tags from them
 */
class DocblockParser
{
    /**
     * $offsetStart is the absolute position of the docblock in the file. It'll be used to add to the position of some
     * special tags (like `psalm-suppress`) for future uses
     */
    public static function parse(string $docblock, int $offsetStart): ParsedDocblock
    {
        // Strip off comments.
        $docblock = trim($docblock);

        if (strpos($docblock, '/**') === 0) {
            $docblock = substr($docblock, 3);
        }

        if (substr($docblock, -2) === '*/') {
            $docblock = substr($docblock, 0, -2);

            if (substr($docblock, -1) === '*') {
                $docblock = substr($docblock, 0, -1);
            }
        }

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $special = [];

        $first_line_padding = null;

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^[ \t]*\*?\s*@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*\r?$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = $old_last_line . "\n" . $line;

                unset($lines[$k]);
            }
        }

        $line_offset = 0;

        foreach ($lines as $k => $line) {
            $original_line_length = strlen($line);

            $line = str_replace("\r", '', $line);

            if ($first_line_padding === null) {
                $asterisk_pos = strpos($line, '*');

                if ($asterisk_pos) {
                    $first_line_padding = substr($line, 0, $asterisk_pos - 1);
                }
            }

            if (preg_match('/^[ \t]*\*?\s*@([\w\-\\\:]+)[\t ]*(.*)$/sm', $line, $matches, PREG_OFFSET_CAPTURE)) {
                /** @var array<int, array{string, int}> $matches */
                [, $type_info, $data_info] = $matches;

                [$type] = $type_info;
                [$data, $data_offset] = $data_info;

                if (strpos($data, '*')) {
                    $data = rtrim(preg_replace('/^[ \t]*\*\s*$/m', '', $data));
                }

                if (empty($special[$type])) {
                    $special[$type] = [];
                }

                $data_offset += $line_offset;

                $special[$type][$data_offset + 3 + $offsetStart] = $data;

                unset($lines[$k]);
            } else {
                // Strip the leading *, if present.
                $text = $lines[$k];
                $text = str_replace("\t", ' ', $text);
                $text = preg_replace('/^ *\*/', '', $text, 1);
                $lines[$k] = $text;
            }

            $line_offset += $original_line_length + 1;
        }

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        foreach ($lines as $k => $line) {
            $indent = strspn($line, ' ');
            if ($indent === strlen($line)) {
                // This line consists of only spaces. Trim it completely.
                $lines[$k] = '';
                continue;
            }
            $min_indent = min($indent, $min_indent);
        }
        if ($min_indent > 0) {
            foreach ($lines as $k => $line) {
                if (strlen($line) < $min_indent) {
                    continue;
                }
                $lines[$k] = substr($line, $min_indent);
            }
        }
        $docblock = implode("\n", $lines);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock, 1);

        $parsed = new ParsedDocblock($docblock, $special, $first_line_padding ?: '');

        self::resolveTags($parsed);

        return $parsed;
    }

    private static function resolveTags(ParsedDocblock $docblock): void
    {
        if (isset($docblock->tags['template'])
            || isset($docblock->tags['psalm-template'])
            || isset($docblock->tags['phpstan-template'])
        ) {
            $docblock->combined_tags['template']
                = ($docblock->tags['template'] ?? [])
                + ($docblock->tags['phpstan-template'] ?? [])
                + ($docblock->tags['psalm-template'] ?? []);
        }

        if (isset($docblock->tags['template-covariant'])
            || isset($docblock->tags['psalm-template-covariant'])
            || isset($docblock->tags['phpstan-template-covariant'])
        ) {
            $docblock->combined_tags['template-covariant']
                = ($docblock->tags['template-covariant'] ?? [])
                + ($docblock->tags['phpstan-template-covariant'] ?? [])
                + ($docblock->tags['psalm-template-covariant'] ?? []);
        }

        if (isset($docblock->tags['template-extends'])
            || isset($docblock->tags['inherits'])
            || isset($docblock->tags['extends'])
            || isset($docblock->tags['psalm-extends'])
            || isset($docblock->tags['phpstan-extends'])
        ) {
            $docblock->combined_tags['extends']
                = ($docblock->tags['template-extends'] ?? [])
                + ($docblock->tags['inherits'] ?? [])
                + ($docblock->tags['extends'] ?? [])
                + ($docblock->tags['psalm-extends'] ?? [])
                + ($docblock->tags['phpstan-extends'] ?? []);
        }

        if (isset($docblock->tags['template-implements'])
            || isset($docblock->tags['implements'])
            || isset($docblock->tags['phpstan-implements'])
            || isset($docblock->tags['psalm-implements'])
        ) {
            $docblock->combined_tags['implements']
                = ($docblock->tags['template-implements'] ?? [])
                + ($docblock->tags['implements'] ?? [])
                + ($docblock->tags['phpstan-implements'] ?? [])
                + ($docblock->tags['psalm-implements'] ?? []);
        }

        if (isset($docblock->tags['template-use'])
            || isset($docblock->tags['use'])
            || isset($docblock->tags['phpstan-use'])
            || isset($docblock->tags['psalm-use'])
        ) {
            $docblock->combined_tags['use']
                = ($docblock->tags['template-use'] ?? [])
                + ($docblock->tags['use'] ?? [])
                + ($docblock->tags['phpstan-use'] ?? [])
                + ($docblock->tags['psalm-use'] ?? []);
        }

        if (isset($docblock->tags['method'])
            || isset($docblock->tags['psalm-method'])
        ) {
            $docblock->combined_tags['method']
                = ($docblock->tags['method'] ?? [])
                + ($docblock->tags['psalm-method'] ?? []);
        }

        if (isset($docblock->tags['return'])
            || isset($docblock->tags['psalm-return'])
            || isset($docblock->tags['phpstan-return'])
        ) {
            if (isset($docblock->tags['psalm-return'])) {
                $docblock->combined_tags['return'] = $docblock->tags['psalm-return'];
            } elseif (isset($docblock->tags['phpstan-return'])) {
                $docblock->combined_tags['return'] = $docblock->tags['phpstan-return'];
            } else {
                $docblock->combined_tags['return'] = $docblock->tags['return'];
            }
        }

        if (isset($docblock->tags['param'])
            || isset($docblock->tags['psalm-param'])
            || isset($docblock->tags['phpstan-param'])
        ) {
            $docblock->combined_tags['param']
                = ($docblock->tags['param'] ?? [])
                + ($docblock->tags['phpstan-param'] ?? [])
                + ($docblock->tags['psalm-param'] ?? []);
        }

        if (isset($docblock->tags['var'])
            || isset($docblock->tags['psalm-var'])
            || isset($docblock->tags['phpstan-var'])
        ) {
            if (!isset($docblock->tags['ignore-var'])
                && !isset($docblock->tags['psalm-ignore-var'])
            ) {
                $docblock->combined_tags['var']
                    = ($docblock->tags['var'] ?? [])
                    + ($docblock->tags['phpstan-var'] ?? [])
                    + ($docblock->tags['psalm-var'] ?? []);
            }
        }

        if (isset($docblock->tags['param-out'])
            || isset($docblock->tags['psalm-param-out'])
        ) {
            $docblock->combined_tags['param-out']
                = ($docblock->tags['param-out'] ?? [])
                + ($docblock->tags['psalm-param-out'] ?? []);
        }
    }

    /**
     * @return list<non-empty-string>
     * @throws DocblockParseException when a @psalm-internal tag doesn't include a namespace
     */
    public static function handlePsalmInternal(ParsedDocblock $parsed_docblock): array
    {
        if (isset($parsed_docblock->tags['psalm-internal'])) {
            $psalm_internal = array_map("trim", $parsed_docblock->tags['psalm-internal']);

            if (count($psalm_internal) !== count(array_filter($psalm_internal))) {
                throw new DocblockParseException('psalm-internal annotation used without specifying namespace');
            }
            // assert($psalm_internal === array_filter($psalm_internal)); // TODO get this to work
            assert(self::assertArrayOfNonEmptyString($psalm_internal));

            return array_values($psalm_internal);
        }

        return [];
    }

    /** @psalm-assert-if-true array<array-key, non-empty-string> $arr */
    private static function assertArrayOfNonEmptyString(array $arr): bool
    {
        foreach ($arr as $val) {
            if (!is_string($val) || $val === "") {
                return false;
            }
        }

        return true;
    }
}
