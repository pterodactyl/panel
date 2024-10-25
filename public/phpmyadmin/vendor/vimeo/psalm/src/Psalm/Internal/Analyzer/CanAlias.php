<?php

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;

use function implode;
use function strtolower;

trait CanAlias
{
    /**
     * @var array<lowercase-string, string>
     */
    private $aliased_classes = [];

    /**
     * @var array<lowercase-string, CodeLocation>
     */
    private $aliased_class_locations = [];

    /**
     * @var array<lowercase-string, string>
     */
    private $aliased_classes_flipped = [];

    /**
     * @var array<lowercase-string, string>
     */
    private $aliased_classes_flipped_replaceable = [];

    /**
     * @var array<lowercase-string, non-empty-string>
     */
    private $aliased_functions = [];

    /**
     * @var array<string, string>
     */
    private $aliased_constants = [];

    public function visitUse(PhpParser\Node\Stmt\Use_ $stmt): void
    {
        $codebase = $this->getCodebase();

        foreach ($stmt->uses as $use) {
            $use_path = implode('\\', $use->name->parts);
            $use_path_lc = strtolower($use_path);
            $use_alias = $use->alias->name ?? $use->name->getLast();
            $use_alias_lc = strtolower($use_alias);

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[$use_alias_lc] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use_alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    $codebase->analyzer->addOffsetReference(
                        $this->getFilePath(),
                        (int) $use->getAttribute('startFilePos'),
                        (int) $use->getAttribute('endFilePos'),
                        $use_path
                    );
                    if ($codebase->collect_locations) {
                        // register the path
                        $codebase->use_referencing_locations[$use_path_lc][] =
                            new CodeLocation($this, $use);
                    }

                    if ($codebase->alter_code) {
                        if (isset($codebase->class_transforms[$use_path_lc])) {
                            $new_fq_class_name = $codebase->class_transforms[$use_path_lc];

                            $file_manipulations = [];

                            $file_manipulations[] = new FileManipulation(
                                (int) $use->getAttribute('startFilePos'),
                                (int) $use->getAttribute('endFilePos') + 1,
                                $new_fq_class_name . ($use->alias ? ' as ' . $use_alias : '')
                            );

                            FileManipulationBuffer::add($this->getFilePath(), $file_manipulations);
                        }

                        $this->aliased_classes_flipped_replaceable[$use_path_lc] = $use_alias;
                    }

                    $this->aliased_classes[$use_alias_lc] = $use_path;
                    $this->aliased_class_locations[$use_alias_lc] = new CodeLocation($this, $stmt);
                    $this->aliased_classes_flipped[$use_path_lc] = $use_alias;
                    break;
            }
        }
    }

    public function visitGroupUse(PhpParser\Node\Stmt\GroupUse $stmt): void
    {
        $use_prefix = implode('\\', $stmt->prefix->parts);

        $codebase = $this->getCodebase();

        foreach ($stmt->uses as $use) {
            $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);
            $use_alias = $use->alias->name ?? $use->name->getLast();

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[strtolower($use_alias)] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use_alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    if ($codebase->collect_locations) {
                        // register the path
                        $codebase->use_referencing_locations[strtolower($use_path)][] =
                            new CodeLocation($this, $use);
                    }

                    $this->aliased_classes[strtolower($use_alias)] = $use_path;
                    $this->aliased_classes_flipped[strtolower($use_path)] = $use_alias;
                    break;
            }
        }
    }

    /**
     * @return array<lowercase-string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        return $this->aliased_classes_flipped;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return $this->aliased_classes_flipped_replaceable;
    }

    public function getAliases(): Aliases
    {
        return new Aliases(
            $this->getNamespace(),
            $this->aliased_classes,
            $this->aliased_functions,
            $this->aliased_constants
        );
    }
}
