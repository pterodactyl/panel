<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\ImpureVariable;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\PossiblyUndefinedGlobalVariable;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedGlobalVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\TaintKindGroup;
use Psalm\Type\Union;

use function in_array;
use function is_string;
use function time;

/**
 * @internal
 */
class VariableFetchAnalyzer
{
    public const SUPER_GLOBALS = [
        '$GLOBALS',
        '$_SERVER',
        '$_GET',
        '$_POST',
        '$_FILES',
        '$_COOKIE',
        '$_SESSION',
        '$_REQUEST',
        '$_ENV',
        '$http_response_header',
    ];

    /**
     * @param bool $from_global - when used in a global keyword
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        bool $passed_by_reference = false,
        ?Union $by_ref_type = null,
        bool $array_assignment = false,
        bool $from_global = false
    ): bool {
        $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->name === 'this') {
            if ($statements_analyzer->isStatic()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a static context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return true;
            }

            if (!isset($context->vars_in_scope['$this'])) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                $context->vars_in_scope['$this'] = Type::getMixed();
                $context->vars_possibly_in_scope['$this'] = true;

                return true;
            }

            $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope['$this']);

            if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
                );
            }

            if (!$context->collect_mutations && !$context->collect_initializations) {
                if ($context->pure) {
                    IssueBuffer::maybeAdd(
                        new ImpureVariable(
                            'Cannot reference $this in a pure context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    $statements_analyzer->getSource()->inferred_impure = true;
                }
            }

            return true;
        }

        if (!$context->check_variables) {
            if (is_string($stmt->name)) {
                $var_name = '$' . $stmt->name;

                if (!$context->hasVariable($var_name)) {
                    $context->vars_in_scope[$var_name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());
                } else {
                    $stmt_type = clone $context->vars_in_scope[$var_name];

                    $statements_analyzer->node_data->setType($stmt, $stmt_type);

                    self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            return true;
        }

        if (is_string($stmt->name) && self::isSuperGlobal('$' . $stmt->name)) {
            $var_name = '$' . $stmt->name;

            if (isset($context->vars_in_scope[$var_name])) {
                $type = clone $context->vars_in_scope[$var_name];

                self::taintVariable($statements_analyzer, $var_name, $type, $stmt);

                $statements_analyzer->node_data->setType($stmt, $type);

                return true;
            }

            $type = self::getGlobalType($var_name, $codebase->analysis_php_version_id);

            self::taintVariable($statements_analyzer, $var_name, $type, $stmt);

            $statements_analyzer->node_data->setType($stmt, $type);
            $context->vars_in_scope[$var_name] = clone $type;
            $context->vars_possibly_in_scope[$var_name] = true;

            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt,
                $var_name
            );

            return true;
        }

        if (!is_string($stmt->name)) {
            if ($context->pure) {
                IssueBuffer::maybeAdd(
                    new ImpureVariable(
                        'Cannot reference an unknown variable in a pure context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } elseif ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_impure = true;
            }

            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;
            $expr_result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
            $context->inside_general_use = $was_inside_general_use;

            return $expr_result;
        }

        if ($passed_by_reference && $by_ref_type) {
            AssignmentAnalyzer::assignByRefParam(
                $statements_analyzer,
                $stmt,
                $by_ref_type,
                $by_ref_type,
                $context
            );

            return true;
        }

        $var_name = '$' . $stmt->name;

        if (!$context->hasVariable($var_name)) {
            if (!isset($context->vars_possibly_in_scope[$var_name])
                || !$statements_analyzer->getFirstAppearance($var_name)
            ) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;

                    // it might have been defined first in another if/else branch
                    if (!$statements_analyzer->hasVariable($var_name)) {
                        $statements_analyzer->registerVariable(
                            $var_name,
                            new CodeLocation($statements_analyzer, $stmt),
                            $context->branch_point
                        );
                    }
                } elseif (!$context->inside_isset
                    || $statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                ) {
                    if ($context->is_global || $from_global) {
                        IssueBuffer::maybeAdd(
                            new UndefinedGlobalVariable(
                                'Cannot find referenced variable ' . $var_name . ' in global scope',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $var_name
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        );

                        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                        return true;
                    }

                    IssueBuffer::maybeAdd(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $var_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );

                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                    return true;
                }
            }

            $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

            if ($first_appearance && !$context->inside_isset && !$context->inside_unset) {
                if ($context->is_global) {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedGlobalVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_name
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
                    );
                } else {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
                    );
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start . '-' . $first_appearance->raw_file_end . ':mixed'
                    );
                }

                $stmt_type = Type::getMixed();

                $statements_analyzer->node_data->setType($stmt, $stmt_type);

                self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);

                $statements_analyzer->registerPossiblyUndefinedVariable($var_name, $stmt);

                return true;
            }
        } else {
            $stmt_type = clone $context->vars_in_scope[$var_name];

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            self::addDataFlowToVariable($statements_analyzer, $stmt, $var_name, $stmt_type, $context);

            if ($stmt_type->possibly_undefined_from_try && !$context->inside_isset) {
                if ($context->is_global) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
                );
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

                if ($first_appearance) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start
                            . '-' . $first_appearance->raw_file_end
                            . ':' . $stmt_type->getId()
                    );
                }
            }
        }

        return true;
    }

    private static function addDataFlowToVariable(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        string $var_name,
        Union $stmt_type,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if ($statements_analyzer->data_flow_graph
            && $codebase->find_unused_variables
            && ($context->inside_return
                || $context->inside_call
                || $context->inside_general_use
                || $context->inside_conditional
                || $context->inside_throw
                || $context->inside_isset)
        ) {
            if (!$stmt_type->parent_nodes) {
                $assignment_node = DataFlowNode::getForAssignment(
                    $var_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                );

                $stmt_type->parent_nodes = [
                    $assignment_node->id => $assignment_node
                ];
            }

            foreach ($stmt_type->parent_nodes as $parent_node) {
                if ($context->inside_call || $context->inside_return) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-call'
                    );
                } elseif ($context->inside_conditional) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-conditional'
                    );
                } elseif ($context->inside_isset) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'use-inside-isset'
                    );
                } else {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode(
                            'variable-use',
                            'variable use',
                            null
                        ),
                        'variable-use'
                    );
                }
            }
        }
    }

    private static function taintVariable(
        StatementsAnalyzer $statements_analyzer,
        string $var_name,
        Union $type,
        PhpParser\Node\Expr\Variable $stmt
    ): void {
        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            if ($var_name === '$_GET'
                || $var_name === '$_POST'
                || $var_name === '$_COOKIE'
                || $var_name === '$_REQUEST'
            ) {
                $taint_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $server_taint_source = new TaintSource(
                    $var_name . ':' . $taint_location->file_name . ':' . $taint_location->raw_file_start,
                    $var_name,
                    null,
                    null,
                    TaintKindGroup::ALL_INPUT
                );

                $statements_analyzer->data_flow_graph->addSource($server_taint_source);

                $type->parent_nodes = [
                    $server_taint_source->id => $server_taint_source
                ];
            }
        }
    }

    /**
     * @psalm-pure
     */
    public static function isSuperGlobal(string $var_id): bool
    {
        return in_array(
            $var_id,
            self::SUPER_GLOBALS,
            true
        );
    }

    public static function getGlobalType(string $var_id, int $codebase_analysis_php_version_id): Union
    {
        $config = Config::getInstance();

        if (isset($config->globals[$var_id])) {
            return Type::parseString($config->globals[$var_id]);
        }

        if ($var_id === '$argv') {
            // only in CLI, null otherwise
            $argv_nullable = new Union([
                new TNonEmptyList(Type::getString()),
                new TNull()
            ]);
            // use TNull explicitly instead of this
            // as it will cause weird errors due to ignore_nullable_issues true
            // e.g. InvalidPropertyAssignmentValue
            // $this->argv 'list<string>' cannot be assigned type 'non-empty-list<string>'
            // $argv_nullable->possibly_undefined = true;
            $argv_nullable->ignore_nullable_issues = true;
            return $argv_nullable;
        }

        if ($var_id === '$argc') {
            // only in CLI, null otherwise
            $argc_nullable = new Union([
                new TIntRange(1, null),
                new TNull()
            ]);
            // $argc_nullable->possibly_undefined = true;
            $argc_nullable->ignore_nullable_issues = true;
            return $argc_nullable;
        }

        if (!self::isSuperGlobal($var_id)) {
            return Type::getMixed();
        }

        if ($var_id === '$http_response_header') {
            return new Union([
                new TList(Type::getNonEmptyString())
            ]);
        }

        if ($var_id === '$GLOBALS') {
            return new Union([
                new TNonEmptyArray([
                    Type::getNonEmptyString(),
                    Type::getMixed()
                ])
            ]);
        }

        if ($var_id === '$_COOKIE') {
            $type = new TArray(
                [
                    Type::getNonEmptyString(),
                    Type::getString(),
                ]
            );

            return new Union([$type]);
        }

        if (in_array($var_id, array('$_GET', '$_POST', '$_REQUEST'), true)) {
            $array_key = new Union([new TNonEmptyString(), new TInt()]);
            $array = new TNonEmptyArray(
                [
                    $array_key,
                    new Union([
                        new TString(),
                        new TArray([
                            $array_key,
                            Type::getMixed()
                        ])
                    ])
                ]
            );

            $type = new TArray(
                [
                    $array_key,
                    new Union([new TString(), $array]),
                ]
            );

            return new Union([$type]);
        }

        if ($var_id === '$_SERVER' || $var_id === '$_ENV') {
            $string_helper = Type::getString();
            $string_helper->possibly_undefined = true;

            $non_empty_string_helper = Type::getNonEmptyString();
            $non_empty_string_helper->possibly_undefined = true;

            $argv_helper = new Union([
                new TNonEmptyList(Type::getString())
            ]);
            $argv_helper->possibly_undefined = true;

            $argc_helper = new Union([
                new TIntRange(1, null)
            ]);
            $argc_helper->possibly_undefined = true;

            $request_time_helper = new Union([
                new TIntRange(time(), null)
            ]);
            $request_time_helper->possibly_undefined = true;

            $request_time_float_helper = Type::getFloat();
            $request_time_float_helper->possibly_undefined = true;

            $bool_string_helper = new Union([new TBool(), new TString()]);
            $bool_string_helper->possibly_undefined = true;

            $detailed_type_members = [
                // https://www.php.net/manual/en/reserved.variables.server.php
                'PHP_SELF'             => $non_empty_string_helper,
                'GATEWAY_INTERFACE'    => $non_empty_string_helper,
                'SERVER_ADDR'          => $non_empty_string_helper,
                'SERVER_NAME'          => $non_empty_string_helper,
                'SERVER_SOFTWARE'      => $non_empty_string_helper,
                'SERVER_PROTOCOL'      => $non_empty_string_helper,
                'REQUEST_METHOD'       => $non_empty_string_helper,
                'REQUEST_TIME'         => $request_time_helper,
                'REQUEST_TIME_FLOAT'   => $request_time_float_helper,
                'QUERY_STRING'         => $string_helper,
                'DOCUMENT_ROOT'        => $non_empty_string_helper,
                'HTTP_ACCEPT'          => $non_empty_string_helper,
                'HTTP_ACCEPT_CHARSET'  => $non_empty_string_helper,
                'HTTP_ACCEPT_ENCODING' => $non_empty_string_helper,
                'HTTP_ACCEPT_LANGUAGE' => $non_empty_string_helper,
                'HTTP_CONNECTION'      => $non_empty_string_helper,
                'HTTP_HOST'            => $non_empty_string_helper,
                'HTTP_REFERER'         => $non_empty_string_helper,
                'HTTP_USER_AGENT'      => $non_empty_string_helper,
                'HTTPS'                => $string_helper,
                'REMOTE_ADDR'          => $non_empty_string_helper,
                'REMOTE_HOST'          => $non_empty_string_helper,
                'REMOTE_PORT'          => $string_helper,
                'REMOTE_USER'          => $non_empty_string_helper,
                'REDIRECT_REMOTE_USER' => $non_empty_string_helper,
                'SCRIPT_FILENAME'      => $non_empty_string_helper,
                'SERVER_ADMIN'         => $non_empty_string_helper,
                'SERVER_PORT'          => $non_empty_string_helper,
                'SERVER_SIGNATURE'     => $non_empty_string_helper,
                'PATH_TRANSLATED'      => $non_empty_string_helper,
                'SCRIPT_NAME'          => $non_empty_string_helper,
                'REQUEST_URI'          => $non_empty_string_helper,
                'PHP_AUTH_DIGEST'      => $non_empty_string_helper,
                'PHP_AUTH_USER'        => $non_empty_string_helper,
                'PHP_AUTH_PW'          => $non_empty_string_helper,
                'AUTH_TYPE'            => $non_empty_string_helper,
                'PATH_INFO'            => $non_empty_string_helper,
                'ORIG_PATH_INFO'       => $non_empty_string_helper,
                // misc from RFC not included above already http://www.faqs.org/rfcs/rfc3875.html
                'CONTENT_LENGTH'       => $string_helper,
                'CONTENT_TYPE'         => $string_helper,
                // common, misc stuff
                'FCGI_ROLE'            => $non_empty_string_helper,
                'HOME'                 => $non_empty_string_helper,
                'HTTP_CACHE_CONTROL'   => $non_empty_string_helper,
                'HTTP_COOKIE'          => $non_empty_string_helper,
                'HTTP_PRIORITY'        => $non_empty_string_helper,
                'PATH'                 => $non_empty_string_helper,
                'REDIRECT_STATUS'      => $non_empty_string_helper,
                'REQUEST_SCHEME'       => $non_empty_string_helper,
                'USER'                 => $non_empty_string_helper,
                // common, misc headers
                'HTTP_UPGRADE_INSECURE_REQUESTS' => $non_empty_string_helper,
                'HTTP_X_FORWARDED_PROTO'         => $non_empty_string_helper,
                'HTTP_CLIENT_IP'                 => $non_empty_string_helper,
                'HTTP_X_REAL_IP'                 => $non_empty_string_helper,
                'HTTP_X_FORWARDED_FOR'           => $non_empty_string_helper,
                'HTTP_CF_CONNECTING_IP'          => $non_empty_string_helper,
                'HTTP_CF_IPCOUNTRY'              => $non_empty_string_helper,
                'HTTP_CF_VISITOR'                => $non_empty_string_helper,
                'HTTP_CDN_LOOP'                  => $non_empty_string_helper,
                // common, misc browser headers
                'HTTP_DNT'                => $non_empty_string_helper,
                'HTTP_SEC_FETCH_DEST'     => $non_empty_string_helper,
                'HTTP_SEC_FETCH_USER'     => $non_empty_string_helper,
                'HTTP_SEC_FETCH_MODE'     => $non_empty_string_helper,
                'HTTP_SEC_FETCH_SITE'     => $non_empty_string_helper,
                'HTTP_SEC_CH_UA_PLATFORM' => $non_empty_string_helper,
                'HTTP_SEC_CH_UA_MOBILE'   => $non_empty_string_helper,
                'HTTP_SEC_CH_UA'          => $non_empty_string_helper,
                // phpunit
                'APP_DEBUG' => $bool_string_helper,
                'APP_ENV'   => $string_helper,
            ];

            if ($var_id === '$_SERVER') {
                // those elements are not usually present in $_ENV
                $detailed_type_members['argc'] = $argc_helper;
                $detailed_type_members['argv'] = $argv_helper;
            }

            $detailed_type = new TKeyedArray($detailed_type_members);

            // generic case for all other elements
            $detailed_type->previous_key_type = Type::getNonEmptyString();
            $detailed_type->previous_value_type = Type::getString();

            return new Union([$detailed_type]);
        }

        if ($var_id === '$_FILES') {
            $values = [
                'name' => new Union([
                    new TString(),
                    new TNonEmptyList(Type::getString()),
                ]),
                'type' => new Union([
                    new TString(),
                    new TNonEmptyList(Type::getString()),
                ]),
                'size' => new Union([
                    new TIntRange(0, null),
                    new TNonEmptyList(Type::getInt()),
                ]),
                'tmp_name' => new Union([
                    new TString(),
                    new TNonEmptyList(Type::getString()),
                ]),
                'error' => new Union([
                    new TIntRange(0, 8),
                    new TNonEmptyList(Type::getInt()),
                ]),
            ];

            if ($codebase_analysis_php_version_id >= 80100) {
                $values['full_path'] = new Union([
                    new TString(),
                    new TNonEmptyList(Type::getString()),
                ]);
            }

            $type = new TKeyedArray($values);

            // $_FILES['userfile']['...'] case
            $named_type = new TArray([Type::getNonEmptyString(), new Union([$type])]);

            // by default $_FILES is an empty array
            $default_type = new TArray([Type::getNever(), Type::getNever()]);

            // ideally we would have 4 separate arrays with distinct types, but that isn't possible with psalm atm
            return TypeCombiner::combine([$default_type, $type, $named_type]);
        }

        if ($var_id === '$_SESSION') {
            // keys must be string
            $type = new Union([
                new TArray([
                    Type::getNonEmptyString(),
                    Type::getMixed(),
                ])
            ]);
            $type->possibly_undefined = true;
            return $type;
        }

        return Type::getMixed();
    }
}
