<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Union;

/**
 * @internal
 */
class FunctionCallInfo
{
    /**
     * @var ?string
     */
    public $function_id;

    /**
     * @var ?bool
     */
    public $function_exists;

    /**
     * @var bool
     */
    public $is_stubbed = false;

    /**
     * @var bool
     */
    public $in_call_map = false;

    /**
     * @var array<string, Union>
     */
    public $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public $global_variables = [];

    /**
     * @var ?array<int, FunctionLikeParameter>
     */
    public $function_params;

    /**
     * @var ?FunctionLikeStorage
     */
    public $function_storage;

    /**
     * @var ?PhpParser\Node\Name
     */
    public $new_function_name;

    /**
     * @var bool
     */
    public $allow_named_args = true;

    /**
     * @var array
     */
    public $byref_uses = [];

    /**
     * @mutation-free
     */
    public function hasByReferenceParameters(): bool
    {
        if (null === $this->function_params) {
            return false;
        }

        foreach ($this->function_params as $value) {
            if ($value->by_ref) {
                return true;
            }
        }

        return false;
    }
}
