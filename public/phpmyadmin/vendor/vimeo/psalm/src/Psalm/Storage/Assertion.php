<?php

namespace Psalm\Storage;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type\Atomic\TTemplateParam;

use function array_map;
use function implode;
use function is_string;
use function str_replace;

class Assertion
{
    /**
     * @var array<int, array<int, string>> the rule being asserted
     */
    public $rule;

    /**
     * @var int|string the id of the property/variable, or
     *  the parameter offset of the affected arg
     */
    public $var_id;

    /**
     * @param string|int $var_id
     * @param array<int, array<int, string>> $rule
     */
    public function __construct($var_id, array $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }

    /**
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    public function getUntemplatedCopy(
        array $inferred_lower_bounds,
        ?string $this_var_id,
        ?Codebase $codebase
    ): self {
        return new Assertion(
            is_string($this->var_id) && $this_var_id
                ? str_replace('$this->', $this_var_id . '->', $this->var_id)
                : $this->var_id,
            array_map(
                /**
                 * @param array<int, string> $rules
                 *
                 * @return array{0: string}
                 */
                function (array $rules) use ($inferred_lower_bounds, $codebase): array {
                    $first_rule = $rules[0];

                    if ($inferred_lower_bounds) {
                        $rule_tokens = TypeTokenizer::tokenize($first_rule);

                        $substitute = false;

                        foreach ($rule_tokens as &$rule_token) {
                            if (isset($inferred_lower_bounds[$rule_token[0]])) {
                                foreach ($inferred_lower_bounds[$rule_token[0]] as $lower_bounds) {
                                    $substitute = true;

                                    $bound_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                        $lower_bounds,
                                        $codebase
                                    );

                                    $first_type = $bound_type->getSingleAtomic();

                                    if ($first_type instanceof TTemplateParam) {
                                        $rule_token[0] = $first_type->param_name;
                                    } else {
                                        $rule_token[0] = $first_type->getId();
                                    }
                                }
                            }
                        }

                        if ($substitute) {
                            return [implode(
                                '',
                                array_map(
                                    function ($f) {
                                        return $f[0];
                                    },
                                    $rule_tokens
                                )
                            )];
                        }
                    }

                    return [$first_rule];
                },
                $this->rule
            )
        );
    }
}
