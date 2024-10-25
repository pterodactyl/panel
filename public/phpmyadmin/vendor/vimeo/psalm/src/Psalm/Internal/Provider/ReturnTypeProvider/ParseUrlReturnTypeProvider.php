<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;

class ParseUrlReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['parse_url'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (isset($call_args[1])) {
            $is_default_component = false;
            if ($component_type = $statements_source->node_data->getType($call_args[1]->value)) {
                if (!$component_type->hasMixed()) {
                    $codebase = $statements_source->getCodebase();

                    $acceptable_string_component_type = new Union([
                        new TLiteralInt(PHP_URL_SCHEME),
                        new TLiteralInt(PHP_URL_USER),
                        new TLiteralInt(PHP_URL_PASS),
                        new TLiteralInt(PHP_URL_HOST),
                        new TLiteralInt(PHP_URL_PATH),
                        new TLiteralInt(PHP_URL_QUERY),
                        new TLiteralInt(PHP_URL_FRAGMENT),
                    ]);

                    $acceptable_int_component_type = new Union([
                        new TLiteralInt(PHP_URL_PORT),
                    ]);

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_string_component_type
                    )) {
                        $nullable_falsable_string = new Union([
                            new TString,
                            new TFalse,
                            new TNull,
                        ]);

                        $codebase = $statements_source->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_falsable_string->ignore_nullable_issues = true;
                        }

                        if ($codebase->config->ignore_internal_falsable_issues) {
                            $nullable_falsable_string->ignore_falsable_issues = true;
                        }

                        return $nullable_falsable_string;
                    }

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_int_component_type
                    )) {
                        $nullable_falsable_int = new Union([
                            new TInt,
                            new TFalse,
                            new TNull,
                        ]);

                        $codebase = $statements_source->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_falsable_int->ignore_nullable_issues = true;
                        }

                        if ($codebase->config->ignore_internal_falsable_issues) {
                            $nullable_falsable_int->ignore_falsable_issues = true;
                        }

                        return $nullable_falsable_int;
                    }

                    if ($component_type->isSingleIntLiteral()) {
                        $component_type_type = $component_type->getSingleIntLiteral();
                        $is_default_component = $component_type_type->value <= -1;
                    }
                }
            }

            if (!$is_default_component) {
                $nullable_string_or_int = new Union([
                    new TString,
                    new TInt,
                    new TNull,
                ]);

                $codebase = $statements_source->getCodebase();

                if ($codebase->config->ignore_internal_nullable_issues) {
                    $nullable_string_or_int->ignore_nullable_issues = true;
                }

                return $nullable_string_or_int;
            }
        }

        $component_types = [
            'scheme' => Type::getString(),
            'user' => Type::getString(),
            'pass' => Type::getString(),
            'host' => Type::getString(),
            'port' => Type::getInt(),
            'path' => Type::getString(),
            'query' => Type::getString(),
            'fragment' => Type::getString(),
        ];

        foreach ($component_types as $component_type) {
            $component_type->possibly_undefined = true;
        }

        $return_type = new Union([
            new TKeyedArray($component_types),
            new TFalse(),
        ]);

        if ($statements_source->getCodebase()->config->ignore_internal_falsable_issues) {
            $return_type->ignore_falsable_issues = true;
        }

        return $return_type;
    }
}
