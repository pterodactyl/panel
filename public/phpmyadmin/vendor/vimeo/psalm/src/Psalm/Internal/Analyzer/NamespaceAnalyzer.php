<?php

namespace Psalm\Internal\Analyzer;

use InvalidArgumentException;
use PhpParser;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Context;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Type;
use Psalm\Type\Union;
use ReflectionProperty;
use UnexpectedValueException;

use function assert;
use function count;
use function implode;
use function preg_replace;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
class NamespaceAnalyzer extends SourceAnalyzer
{
    use CanAlias;

    /**
     * @var FileAnalyzer
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected $source;

    /**
     * @var Namespace_
     */
    private $namespace;

    /**
     * @var string
     */
    private $namespace_name;

    /**
     * A lookup table for public namespace constants
     *
     * @var array<string, array<string, Union>>
     */
    protected static $public_namespace_constants = [];

    public function __construct(Namespace_ $namespace, FileAnalyzer $source)
    {
        $this->source = $source;
        $this->namespace = $namespace;
        $this->namespace_name = $this->namespace->name ? implode('\\', $this->namespace->name->parts) : '';
    }

    public function collectAnalyzableInformation(): void
    {
        $leftover_stmts = [];

        if (!isset(self::$public_namespace_constants[$this->namespace_name])) {
            self::$public_namespace_constants[$this->namespace_name] = [];
        }

        $codebase = $this->getCodebase();

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->collectAnalyzableClassLike($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    self::$public_namespace_constants[$this->namespace_name][$const->name->name] = Type::getMixed();
                }

                $leftover_stmts[] = $stmt;
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statements_analyzer = new StatementsAnalyzer($this, new NodeDataProvider());
            $context = new Context();
            $context->is_global = true;
            $context->defineGlobals();
            $context->collect_exceptions = $codebase->config->check_for_throws_in_global_scope;
            $statements_analyzer->analyze($leftover_stmts, $context, null, true);

            $file_context = $this->source->context;
            if ($file_context) {
                $file_context->mergeExceptions($context);
            }
        }
    }

    public function collectAnalyzableClassLike(PhpParser\Node\Stmt\ClassLike $stmt): void
    {
        if (!$stmt->name) {
            throw new UnexpectedValueException('Did not expect anonymous class here');
        }

        $fq_class_name = Type::getFQCLNFromString($stmt->name->name, $this->getAliases());

        if ($stmt instanceof PhpParser\Node\Stmt\Class_ || $stmt instanceof PhpParser\Node\Stmt\Enum_) {
            $this->source->addNamespacedClassAnalyzer(
                $fq_class_name,
                new ClassAnalyzer($stmt, $this, $fq_class_name)
            );
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $this->source->addNamespacedInterfaceAnalyzer(
                $fq_class_name,
                new InterfaceAnalyzer($stmt, $this, $fq_class_name)
            );
        }
    }

    public function getNamespace(): string
    {
        return $this->namespace_name;
    }

    public function setConstType(string $const_name, Union $const_type): void
    {
        self::$public_namespace_constants[$this->namespace_name][$const_name] = $const_type;
    }

    /**
     * @return array<string, Union>
     */
    public static function getConstantsForNamespace(string $namespace_name, int $visibility): array
    {
        // @todo this does not allow for loading in namespace constants not already defined in the current sweep
        if (!isset(self::$public_namespace_constants[$namespace_name])) {
            self::$public_namespace_constants[$namespace_name] = [];
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_namespace_constants[$namespace_name];
        }

        throw new InvalidArgumentException('Given $visibility not supported');
    }

    public function getFileAnalyzer(): FileAnalyzer
    {
        return $this->source;
    }

    /**
     * Returns true if $calling_identifier is the same as, or is within with $identifier, in a
     * case-insensitive comparison. Identifiers can be namespaces, classlikes, functions, or methods.
     *
     * @psalm-pure
     *
     * @throws InvalidArgumentException if $identifier is not a valid identifier
     */
    public static function isWithin(string $calling_identifier, string $identifier): bool
    {
        $normalized_calling_ident = self::normalizeIdentifier($calling_identifier);
        $normalized_ident = self::normalizeIdentifier($identifier);

        if ($normalized_calling_ident === $normalized_ident) {
            return true;
        }

        $normalized_calling_ident_parts = self::getIdentifierParts($normalized_calling_ident);
        $normalized_ident_parts = self::getIdentifierParts($normalized_ident);

        if (count($normalized_calling_ident_parts) < count($normalized_ident_parts)) {
            return false;
        }

        for ($i = 0; $i < count($normalized_ident_parts); ++$i) {
            if ($normalized_ident_parts[$i] !== $normalized_calling_ident_parts[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if $calling_identifier is the same as or is within any identifier
     * in $identifiers in a case-insensitive comparison, or if $identifiers is empty.
     * Identifiers can be namespaces, classlikes, functions, or methods.
     *
     * @psalm-pure
     *
     * @psalm-assert-if-false !empty $identifiers
     *
     * @param list<string> $identifiers
     */
    public static function isWithinAny(string $calling_identifier, array $identifiers): bool
    {
        if (count($identifiers) === 0) {
            return true;
        }

        foreach ($identifiers as $identifier) {
            if (self::isWithin($calling_identifier, $identifier)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-string $fullyQualifiedClassName, e.g. '\Psalm\Internal\Analyzer\NamespaceAnalyzer'
     *
     * @return non-empty-string , e.g. 'Psalm'
     *
     * @psalm-pure
     */
    public static function getNameSpaceRoot(string $fullyQualifiedClassName): string
    {
        $root_namespace = preg_replace('/^([^\\\]+).*/', '$1', $fullyQualifiedClassName, 1);
        if ($root_namespace === "") {
            throw new InvalidArgumentException("Invalid classname \"$fullyQualifiedClassName\"");
        }
        return $root_namespace;
    }

    /**
     * @return ($lowercase is true ? lowercase-string : string)
     *
     * @psalm-pure
     */
    public static function normalizeIdentifier(string $identifier, bool $lowercase = true): string
    {
        if ($identifier === "") {
            return "";
        }

        $identifier = $identifier[0] === "\\" ? substr($identifier, 1) : $identifier;
        return $lowercase ? strtolower($identifier) : $identifier;
    }

    /**
     * Splits an identifier into parts, eg `Foo\Bar::baz` becomes ["Foo", "\\", "Bar", "::", "baz"].
     *
     * @return list<non-empty-string>
     *
     * @psalm-pure
     */
    public static function getIdentifierParts(string $identifier): array
    {
        $parts = [];
        while (($pos = strpos($identifier, "\\")) !== false) {
            if ($pos > 0) {
                $part = substr($identifier, 0, $pos);
                assert($part !== "");
                $parts[] = $part;
            }
            $parts[] = "\\";
            $identifier = substr($identifier, $pos + 1);
        }
        if (($pos = strpos($identifier, "::")) !== false) {
            if ($pos > 0) {
                $part = substr($identifier, 0, $pos);
                assert($part !== "");
                $parts[] = $part;
            }
            $parts[] = "::";
            $identifier = substr($identifier, $pos + 2);
        }
        if ($identifier !== "") {
            $parts[] = $identifier;
        }

        return $parts;
    }
}
