<?php

namespace Psalm\Internal\PhpVisitor\Reflector;

use Exception;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Scanner\ClassLikeDocblockComment;
use Psalm\Internal\Scanner\DocblockParser;
use Psalm\Internal\Type\ParseTree\MethodParamTree;
use Psalm\Internal\Type\ParseTree\MethodTree;
use Psalm\Internal\Type\ParseTree\MethodWithReturnTypeTree;
use Psalm\Internal\Type\ParseTreeCreator;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;

use function array_key_first;
use function array_shift;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function preg_split;
use function reset;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

use const PREG_OFFSET_CAPTURE;

/**
 * @internal
 */
class ClassLikeDocblockParser
{
    /**
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function parse(
        Node $node,
        Doc $comment,
        Aliases $aliases
    ): ClassLikeDocblockComment {
        $parsed_docblock = DocComment::parsePreservingLength($comment);
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        $info = new ClassLikeDocblockComment();

        $templates = [];
        if (isset($parsed_docblock->combined_tags['template'])) {
            foreach ($parsed_docblock->combined_tags['template'] as $offset => $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template tag');
                }

                $source_prefix = 'none';
                if (isset($parsed_docblock->tags['psalm-template'][$offset])) {
                    $source_prefix = 'psalm';
                } elseif (isset($parsed_docblock->tags['phpstan-template'][$offset])) {
                    $source_prefix = 'phpstan';
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $templates[$template_name][$source_prefix] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        false,
                        $offset - $comment->getStartFilePos()
                    ];
                } else {
                    $templates[$template_name][$source_prefix] = [
                        $template_name,
                        null,
                        null,
                        false,
                        $offset - $comment->getStartFilePos()
                    ];
                }
            }
        }

        if (isset($parsed_docblock->combined_tags['template-covariant'])) {
            foreach ($parsed_docblock->combined_tags['template-covariant'] as $offset => $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template-covariant tag');
                }

                $source_prefix = 'none';
                if (isset($parsed_docblock->tags['psalm-template-covariant'][$offset])) {
                    $source_prefix = 'psalm';
                } elseif (isset($parsed_docblock->tags['phpstan-template-covariant'][$offset])) {
                    $source_prefix = 'phpstan';
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $templates[$template_name][$source_prefix] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        true,
                        $offset - $comment->getStartFilePos()
                    ];
                } else {
                    $templates[$template_name][$source_prefix] = [
                        $template_name,
                        null,
                        null,
                        true,
                        $offset - $comment->getStartFilePos()
                    ];
                }
            }
        }

        foreach ($templates as $template_entries) {
            foreach (['psalm', 'phpstan', 'none'] as $source_prefix) {
                if (isset($template_entries[$source_prefix])) {
                    $info->templates[] = $template_entries[$source_prefix];
                    break;
                }
            }
        }

        if (isset($parsed_docblock->combined_tags['extends'])) {
            foreach ($parsed_docblock->combined_tags['extends'] as $template_line) {
                $doc_line_parts = CommentAnalyzer::splitDocLine($template_line);
                $doc_line_parts[0] = CommentAnalyzer::sanitizeDocblockType($doc_line_parts[0]);
                $info->template_extends[] = $doc_line_parts[0];
            }
        }

        if (isset($parsed_docblock->tags['psalm-require-extends'])
            && count($extension_requirements = $parsed_docblock->tags['psalm-require-extends']) > 0) {
            $info->extension_requirement = trim(preg_replace(
                '@^[ \t]*\*@m',
                '',
                $extension_requirements[array_key_first($extension_requirements)]
            ));
        }

        if (isset($parsed_docblock->tags['psalm-require-implements'])) {
            foreach ($parsed_docblock->tags['psalm-require-implements'] as $implementation_requirement) {
                $info->implementation_requirements[] = trim(preg_replace(
                    '@^[ \t]*\*@m',
                    '',
                    $implementation_requirement
                ));
            }
        }

        if (isset($parsed_docblock->combined_tags['implements'])) {
            foreach ($parsed_docblock->combined_tags['implements'] as $template_line) {
                $doc_line_parts = CommentAnalyzer::splitDocLine($template_line);
                $doc_line_parts[0] = CommentAnalyzer::sanitizeDocblockType($doc_line_parts[0]);
                $info->template_implements[] = $doc_line_parts[0];
            }
        }

        if (isset($parsed_docblock->tags['psalm-yield'])) {
            $yield = reset($parsed_docblock->tags['psalm-yield']);

            $info->yield = trim(preg_replace('@^[ \t]*\*@m', '', $yield));
        }

        if (isset($parsed_docblock->tags['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($parsed_docblock->tags['internal'])) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['final'])) {
            $info->final = true;
        }

        if (isset($parsed_docblock->tags['psalm-consistent-constructor'])) {
            $info->consistent_constructor = true;
        }

        if (isset($parsed_docblock->tags['psalm-consistent-templates'])) {
            $info->consistent_templates = true;
        }

        if (count($info->psalm_internal = DocblockParser::handlePsalmInternal($parsed_docblock)) !== 0) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['mixin'])) {
            foreach ($parsed_docblock->tags['mixin'] as $rawMixin) {
                $mixin = trim($rawMixin);
                $doc_line_parts = CommentAnalyzer::splitDocLine($mixin);
                $mixin = $doc_line_parts[0];

                if ($mixin) {
                    $info->mixins[] = $mixin;
                } else {
                    throw new DocblockParseException('@mixin annotation used without specifying class');
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-seal-properties'])) {
            $info->sealed_properties = true;
        }

        if (isset($parsed_docblock->tags['psalm-seal-methods'])) {
            $info->sealed_methods = true;
        }

        if (isset($parsed_docblock->tags['psalm-immutable'])
            || isset($parsed_docblock->tags['psalm-mutation-free'])
        ) {
            $info->mutation_free = true;
            $info->external_mutation_free = true;
            $info->taint_specialize = true;
        }

        if (isset($parsed_docblock->tags['psalm-external-mutation-free'])) {
            $info->external_mutation_free = true;
        }

        if (isset($parsed_docblock->tags['psalm-taint-specialize'])) {
            $info->taint_specialize = true;
        }

        if (isset($parsed_docblock->tags['psalm-override-property-visibility'])) {
            $info->override_property_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-override-method-visibility'])) {
            $info->override_method_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $info->suppressed_issues[$issue_offset + $offset] = $suppressed_issue;
                }
            }
        }

        $imported_types = ($parsed_docblock->tags['phpstan-import-type'] ?? []) +
            ($parsed_docblock->tags['psalm-import-type'] ?? []);

        foreach ($imported_types as $offset => $imported_type_entry) {
            $info->imported_types[] = [
                'line_number' => $comment->getStartLine() + substr_count(
                    $comment->getText(),
                    "\n",
                    0,
                    $offset - $comment->getStartFilePos()
                ),
                'start_offset' => $offset,
                'end_offset' => $offset + strlen($imported_type_entry),
                'parts' => CommentAnalyzer::splitDocLine($imported_type_entry)
            ];
        }

        if (isset($parsed_docblock->combined_tags['method'])) {
            foreach ($parsed_docblock->combined_tags['method'] as $offset => $method_entry) {
                $method_entry = preg_replace('/[ \t]+/', ' ', trim($method_entry));

                $docblock_lines = [];

                $is_static = false;

                $has_return = false;

                if (!preg_match('/^([a-z_A-Z][a-z_0-9A-Z]+) *\(/', $method_entry, $matches)) {
                    $doc_line_parts = CommentAnalyzer::splitDocLine($method_entry);

                    if ($doc_line_parts[0] === 'static' && !strpos($doc_line_parts[1], '(')) {
                        $is_static = true;
                        array_shift($doc_line_parts);
                    }

                    if (count($doc_line_parts) > 1) {
                        $docblock_lines[] = '@return ' . array_shift($doc_line_parts);
                        $has_return = true;

                        $method_entry = implode(' ', $doc_line_parts);
                    }
                }

                $method_entry = trim(preg_replace('/\/\/.*/', '', $method_entry));

                $method_entry = preg_replace(
                    '/array\(([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\)/',
                    '[]',
                    $method_entry
                );

                $end_of_method_regex = '/(?<!array\()\) ?(\: ?(\??[\\\\a-zA-Z0-9_]+))?/';

                if (preg_match($end_of_method_regex, $method_entry, $matches, PREG_OFFSET_CAPTURE)) {
                    $method_entry = substr($method_entry, 0, $matches[0][1] + strlen($matches[0][0]));
                }

                $method_entry = str_replace([', ', '( '], [',', '('], $method_entry);
                $method_entry = preg_replace('/ (?!(\$|\.\.\.|&))/', '', trim($method_entry));

                // replace array bracket contents
                $method_entry = preg_replace('/\[([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\]/', '[]', $method_entry);

                if (!$method_entry) {
                    throw new DocblockParseException('No @method entry specified');
                }

                try {
                    $parse_tree_creator = new ParseTreeCreator(
                        TypeTokenizer::getFullyQualifiedTokens(
                            $method_entry,
                            $aliases,
                            null
                        )
                    );

                    $method_tree = $parse_tree_creator->create();
                } catch (TypeParseTreeException $e) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if (!$method_tree instanceof MethodWithReturnTypeTree
                    && !$method_tree instanceof MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if ($method_tree instanceof MethodWithReturnTypeTree) {
                    if (!$has_return) {
                        $docblock_lines[] = '@return ' . TypeParser::getTypeFromTree(
                            $method_tree->children[1],
                            $codebase
                        )->toNamespacedString($aliases->namespace, $aliases->uses, null, false);
                    }

                    $method_tree = $method_tree->children[0];
                }

                if (!$method_tree instanceof MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                $args = [];

                foreach ($method_tree->children as $method_tree_child) {
                    if (!$method_tree_child instanceof MethodParamTree) {
                        throw new DocblockParseException($method_entry . ' is not a valid method');
                    }

                    $args[] = ($method_tree_child->byref ? '&' : '')
                        . ($method_tree_child->variadic ? '...' : '')
                        . $method_tree_child->name
                        . ($method_tree_child->default != '' ? ' = ' . $method_tree_child->default : '');


                    if ($method_tree_child->children) {
                        try {
                            $param_type = TypeParser::getTypeFromTree($method_tree_child->children[0], $codebase);
                        } catch (Exception $e) {
                            throw new DocblockParseException(
                                'Badly-formatted @method string ' . $method_entry . ' - ' . $e
                            );
                        }

                        $param_type_string = $param_type->toNamespacedString('\\', [], null, false);
                        $docblock_lines[] = '@param ' . $param_type_string . ' '
                            . ($method_tree_child->variadic ? '...' : '')
                            . $method_tree_child->name;
                    }
                }

                $function_string = 'function ' . $method_tree->value . '(' . implode(', ', $args) . ')';

                if ($is_static) {
                    $function_string = 'static ' . $function_string;
                }

                $function_docblock = $docblock_lines ? "/**\n * " . implode("\n * ", $docblock_lines) . "\n*/\n" : "";

                $php_string = '<?php class A { ' . $function_docblock . ' public ' . $function_string . '{} }';

                try {
                    $has_errors = false;

                    $statements = StatementsProvider::parseStatements(
                        $php_string,
                        $codebase->php_major_version . '.' . $codebase->php_minor_version,
                        $has_errors
                    );
                } catch (Exception $e) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                if (!$statements
                    || !$statements[0] instanceof Class_
                    || !isset($statements[0]->stmts[0])
                    || !$statements[0]->stmts[0] instanceof ClassMethod
                ) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                /** @var Doc */
                $node_doc_comment = $node->getDocComment();

                $method_offset = self::getMethodOffset($comment, $method_entry);

                $statements[0]->stmts[0]->setAttribute('startLine', $node_doc_comment->getStartLine() + $method_offset);
                $statements[0]->stmts[0]->setAttribute('startFilePos', $node_doc_comment->getStartFilePos());
                $statements[0]->stmts[0]->setAttribute('endFilePos', $node->getAttribute('startFilePos'));

                if ($doc_comment = $statements[0]->stmts[0]->getDocComment()) {
                    $statements[0]->stmts[0]->setDocComment(
                        new Doc(
                            $doc_comment->getText(),
                            $comment->getStartLine() + substr_count(
                                $comment->getText(),
                                "\n",
                                0,
                                $offset - $comment->getStartFilePos()
                            ),
                            $node_doc_comment->getStartFilePos()
                        )
                    );
                }

                $info->methods[] = $statements[0]->stmts[0];
            }
        }

        if (isset($parsed_docblock->tags['psalm-stub-override'])) {
            $info->stub_override = true;
        }

        if ($parsed_docblock->description) {
            $info->description = $parsed_docblock->description;
        }

        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-write');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-write');

        return $info;
    }

    /**
     * @param array<string, array<int, string>> $specials
     * @param 'property'|'psalm-property'|'property-read'|
     *     'psalm-property-read'|'property-write'|'psalm-property-write' $property_tag
     *
     * @throws DocblockParseException
     *
     */
    protected static function addMagicPropertyToInfo(
        Doc $comment,
        ClassLikeDocblockComment $info,
        array $specials,
        string $property_tag
    ): void {
        $magic_property_comments = $specials[$property_tag] ?? [];

        foreach ($magic_property_comments as $offset => $property) {
            $line_parts = CommentAnalyzer::splitDocLine($property);

            if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                continue;
            }

            if (count($line_parts) > 1) {
                if (preg_match('/^&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                    && $line_parts[0][0] !== '{'
                ) {
                    $line_parts[1] = str_replace('&', '', $line_parts[1]);

                    $line_parts[1] = preg_replace('/,$/', '', $line_parts[1], 1);

                    $end = $offset + strlen($line_parts[0]);

                    $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                    if ($line_parts[0] === ''
                        || ($line_parts[0][0] === '$'
                            && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                    ) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    $name = trim($line_parts[1]);

                    if (!preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $name)) {
                        throw new DocblockParseException('Badly-formatted @property name');
                    }

                    $info->properties[] = [
                        'name' => $name,
                        'type' => $line_parts[0],
                        'line_number' => $comment->getStartLine() + substr_count(
                            $comment->getText(),
                            "\n",
                            0,
                            $offset - $comment->getStartFilePos()
                        ),
                        'tag' => $property_tag,
                        'start' => $offset,
                        'end' => $end,
                    ];
                }
            } else {
                throw new DocblockParseException('Badly-formatted @property');
            }
        }
    }

    private static function getMethodOffset(Doc $comment, string $method_entry): int
    {
        $lines = explode("\n", $comment->getText());
        $method_offset = 0;

        foreach ($lines as $i => $line) {
            if (strpos($line, $method_entry) !== false) {
                $method_offset = $i;
                break;
            }
        }

        return $method_offset;
    }
}
