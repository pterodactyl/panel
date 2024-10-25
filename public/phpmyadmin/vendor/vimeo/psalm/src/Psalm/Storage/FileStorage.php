<?php

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Union;

class FileStorage
{
    use CustomMetadataTrait;

    /**
     * @var array<lowercase-string, string>
     */
    public $classlikes_in_file = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $referenced_classlikes = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $required_classes = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $required_interfaces = [];

    /** @var string */
    public $file_path;

    /**
     * @var array<string, FunctionStorage>
     */
    public $functions = [];

    /** @var array<string, string> */
    public $declaring_function_ids = [];

    /**
     * @var array<string, Union>
     */
    public $constants = [];

    /** @var array<string, string> */
    public $declaring_constants = [];

    /** @var array<lowercase-string, string> */
    public $required_file_paths = [];

    /** @var array<lowercase-string, string> */
    public $required_by_file_paths = [];

    /** @var bool */
    public $populated = false;

    /** @var bool */
    public $deep_scan = false;

    /** @var bool */
    public $has_extra_statements = false;

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var bool
     */
    public $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public $docblock_issues = [];

    /**
     * @var array<string, TypeAlias>
     */
    public $type_aliases = [];

    /**
     * @var array<string, string>
     */
    public $classlike_aliases = [];

    /** @var ?Aliases */
    public $aliases;

    /** @var Aliases[] */
    public $namespace_aliases = [];

    public function __construct(string $file_path)
    {
        $this->file_path = $file_path;
    }
}
