<?php

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeAlias\ClassTypeAlias;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_values;

class ClassLikeStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;

    /**
     * @var array<string, ClassConstantStorage>
     */
    public $constants = [];

    /**
     * Aliases to help Psalm understand constant refs
     *
     * @var ?Aliases
     */
    public $aliases;

    /**
     * @var bool
     */
    public $populated = false;

    /**
     * @var bool
     */
    public $stubbed = false;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var list<non-empty-string>
     */
    public $internal = [];

    /**
     * @var TTemplateParam[]
     */
    public $templatedMixins = [];

    /**
     * @var list<TNamedObject>
     */
    public $namedMixins = [];

    /**
     * @var ?string
     */
    public $mixin_declaring_fqcln;

    /**
     * @var bool
     */
    public $sealed_properties = false;

    /**
     * @var bool
     */
    public $sealed_methods = false;

    /**
     * @var bool
     */
    public $override_property_visibility = false;

    /**
     * @var bool
     */
    public $override_method_visibility = false;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var string
     */
    public $name;

    /**
     * Is this class user-defined
     *
     * @var bool
     */
    public $user_defined = false;

    /**
     * Interfaces this class implements directly
     *
     * @var array<lowercase-string, string>
     */
    public $direct_class_interfaces = [];

    /**
     * Interfaces this class implements explicitly and implicitly
     *
     * @var array<lowercase-string, string>
     */
    public $class_implements = [];

    /**
     * Parent interfaces listed explicitly
     *
     * @var array<lowercase-string, string>
     */
    public $direct_interface_parents = [];

    /**
     * Parent interfaces
     *
     * @var  array<lowercase-string, string>
     */
    public $parent_interfaces = [];

    /**
     * There can only be one direct parent class
     *
     * @var ?string
     */
    public $parent_class;

    /**
     * Parent classes
     *
     * @var array<lowercase-string, string>
     */
    public $parent_classes = [];

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $stmt_location;

    /**
     * @var CodeLocation|null
     */
    public $namespace_name_location;

    /**
     * @var bool
     */
    public $abstract = false;

    /**
     * @var bool
     */
    public $final = false;

    /**
     * @var bool
     */
    public $final_from_docblock = false;

    /**
     * @var array<lowercase-string, string>
     */
    public $used_traits = [];

    /**
     * @var array<lowercase-string, lowercase-string>
     */
    public $trait_alias_map = [];

    /**
     * @var array<lowercase-string, bool>
     */
    public $trait_final_map = [];

    /**
     * @var array<string, int>
     */
    public $trait_visibility_map = [];

    /**
     * @var bool
     */
    public $is_trait = false;

    /**
     * @var bool
     */
    public $is_interface = false;

    /**
     * @var bool
     */
    public $is_enum = false;

    /**
     * @var bool
     */
    public $external_mutation_free = false;

    /**
     * @var bool
     */
    public $mutation_free = false;

    /**
     * @var bool
     */
    public $specialize_instance = false;

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public $methods = [];

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public $pseudo_methods = [];

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public $pseudo_static_methods = [];

    /**
     * Maps pseudo method names to the original declaring method identifier
     * The key is the method name in lowercase, and the value is the original `MethodIdentifier` instance
     *
     * This property contains all pseudo methods declared on ancestors.
     *
     * @var array<lowercase-string, MethodIdentifier>
     */
    public $declaring_pseudo_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public $declaring_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public $appearing_method_ids = [];

    /**
     * Map from lowercase method name to list of declarations in order from parent, to grandparent, to
     * great-grandparent, etc **including traits and interfaces**. Ancestors that don't have their own declaration are
     * skipped.
     *
     * @var array<lowercase-string, array<string, MethodIdentifier>>
     */
    public $overridden_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public $documenting_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public $inheritable_method_ids = [];

    /**
     * @var array<lowercase-string, array<string, bool>>
     */
    public $potential_declaring_method_ids = [];

    /**
     * @var array<string, PropertyStorage>
     */
    public $properties = [];

    /**
     * @var array<string, Union>
     */
    public $pseudo_property_set_types = [];

    /**
     * @var array<string, Union>
     */
    public $pseudo_property_get_types = [];

    /**
     * @var array<string, string>
     */
    public $declaring_property_ids = [];

    /**
     * @var array<string, string>
     */
    public $appearing_property_ids = [];

    /**
     * @var array<string, string>
     */
    public $inheritable_property_ids = [];

    /**
     * @var array<string, array<string>>
     */
    public $overridden_property_ids = [];

    /**
     * An array holding the class template "as" types.
     *
     * It's the de-facto list of all templates on a given class.
     *
     * The name of the template is the first key. The nested array is keyed by the defining class
     * (i.e. the same as the class name). This allows operations with the same-named template defined
     * across multiple classes to not run into trouble.
     *
     * @var array<string, non-empty-array<string, Union>>|null
     */
    public $template_types;

    /**
     * @var array<int, bool>|null
     */
    public $template_covariants;

    /**
     * A map of which generic classlikes are extended or implemented by this class or interface.
     *
     * This is only used in the populator, which poulates the $template_extended_params property below.
     *
     * @internal
     *
     * @var array<string, non-empty-array<int, Union>>|null
     */
    public $template_extended_offsets;

    /**
     * A map of which generic classlikes are extended or implemented by this class or interface.
     *
     * The annotation "@extends Traversable<SomeClass, SomeOtherClass>" would generate an entry of
     *
     * [
     *     "Traversable" => [
     *         "TKey" => new Union([new TNamedObject("SomeClass")]),
     *         "TValue" => new Union([new TNamedObject("SomeOtherClass")])
     *     ]
     * ]
     *
     * @var array<string, array<string, Union>>|null
     */
    public $template_extended_params;

    /**
     * @deprecated Will be replaced with $template_type_extends_count in Psalm v5
     * @var ?int
     */
    public $template_extended_count;

    /**
     * @var array<string, int>|null
     */
    public $template_type_implements_count;

    /**
     * @var ?Union
     */
    public $yield;

    /**
     * @var array<string, int>|null
     */
    public $template_type_uses_count;

    /**
     * @var array<string, bool>
     */
    public $initialized_properties = [];

    /**
     * @var array<string>
     */
    public $invalid_dependencies = [];

    /**
     * @var array<lowercase-string, bool>
     */
    public $dependent_classlikes = [];

    /**
     * A hash of the source file's name, contents, and this file's modified on date
     *
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
     * @var array<string, ClassTypeAlias>
     */
    public $type_aliases = [];

    /**
     * @var bool
     */
    public $preserve_constructor_signature = false;

    /**
     * @var bool
     */
    public $enforce_template_inheritance = false;

    /**
     * @var null|string
     */
    public $extension_requirement;

    /**
     * @var array<int, string>
     */
    public $implementation_requirements = [];

    /**
     * @var list<AttributeStorage>
     */
    public $attributes = [];

    /**
     * @var array<string, EnumCaseStorage>
     */
    public $enum_cases = [];

    /**
     * @var 'int'|'string'|null
     */
    public $enum_type;

    /**
     * @var ?string
     */
    public $description;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return list<AttributeStorage>
     */
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }

    /**
     * Get the template constraint types for the class.
     *
     * @return list<Union>
     */
    public function getClassTemplateTypes(): array
    {
        $type_params = [];

        foreach ($this->template_types ?? [] as $type_map) {
            $type_params[] = clone array_values($type_map)[0];
        }

        return $type_params;
    }
}
