<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;

use function array_map;

/**
 * This class captures the result of running Psalm's argument analysis with
 * regard to generic parameters.
 *
 * It captures upper and lower bounds for parameters. Mostly we just care about
 * lower bounds — those are captured when calling a function that expects a
 * non-callable templated argument.
 *
 * Upper bounds are found in callable parameter types. Given a parameter type
 * `callable(T1): void` and an argument typed as `callable(int): void`, `int` will
 * be added as an _upper_ bound for the template param `T1`. This only applies to
 * parameters — given a parameter type `callable(): T2` and an argument typed as
 * `callable(): string`, `string` will be added as a _lower_ bound for the template
 * param `T2`.
 */
class TemplateResult
{
    /**
     * @var array<string, array<string, Union>>
     */
    public $template_types;

    /**
     * @var array<string, array<string, non-empty-list<TemplateBound>>>
     */
    public $lower_bounds;

    /**
     * @var array<string, array<string, TemplateBound>>
     */
    public $upper_bounds = [];

    /**
     * If set to true then we shouldn't update the template bounds
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * @var list<Union>
     */
    public $upper_bounds_unintersectable_types = [];

    /**
     * @param  array<string, array<string, Union>> $template_types
     * @param  array<string, array<string, Union>> $lower_bounds
     */
    public function __construct(array $template_types, array $lower_bounds)
    {
        $this->template_types = $template_types;

        $this->lower_bounds = array_map(
            function ($type_map) {
                return array_map(
                    function ($type) {
                        return [new TemplateBound($type)];
                    },
                    $type_map
                );
            },
            $lower_bounds
        );
    }
}
