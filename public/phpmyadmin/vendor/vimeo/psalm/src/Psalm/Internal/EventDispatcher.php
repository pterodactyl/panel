<?php

namespace Psalm\Internal;

use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterClassLikeExistenceCheckInterface;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterStatementAnalysisInterface;
use Psalm\Plugin\EventHandler\BeforeFileAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeExistenceCheckEvent;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeFileAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Plugin\EventHandler\StringInterpreterInterface;
use Psalm\Plugin\Hook\AfterAnalysisInterface as LegacyAfterAnalysisInterface;
use Psalm\Plugin\Hook\AfterClassLikeAnalysisInterface as LegacyAfterClassLikeAnalysisInterface;
use Psalm\Plugin\Hook\AfterClassLikeExistenceCheckInterface as LegacyAfterClassLikeExistenceCheckInterface;
use Psalm\Plugin\Hook\AfterClassLikeVisitInterface as LegacyAfterClassLikeVisitInterface;
use Psalm\Plugin\Hook\AfterCodebasePopulatedInterface as LegacyAfterCodebasePopulatedInterface;
use Psalm\Plugin\Hook\AfterEveryFunctionCallAnalysisInterface as LegacyAfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface as LegacyAfterExpressionAnalysisInterface;
use Psalm\Plugin\Hook\AfterFileAnalysisInterface as LegacyAfterFileAnalysisInterface;
use Psalm\Plugin\Hook\AfterFunctionCallAnalysisInterface as LegacyAfterFunctionCallAnalysisInterface;
use Psalm\Plugin\Hook\AfterFunctionLikeAnalysisInterface as LegacyAfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface as LegacyAfterMethodCallAnalysisInterface;
use Psalm\Plugin\Hook\AfterStatementAnalysisInterface as LegacyAfterStatementAnalysisInterface;
use Psalm\Plugin\Hook\BeforeFileAnalysisInterface as LegacyBeforeFileAnalysisInterface;
use Psalm\Plugin\Hook\StringInterpreterInterface as LegacyStringInterpreterInterface;
use Psalm\Type\Atomic\TLiteralString;

use function array_merge;
use function count;
use function is_subclass_of;

class EventDispatcher
{
    /**
     * Static methods to be called after method checks have completed
     *
     * @var list<class-string<AfterMethodCallAnalysisInterface>>
     */
    private $after_method_checks = [];
    /** @var list<class-string<LegacyAfterMethodCallAnalysisInterface>> */
    private $legacy_after_method_checks = [];

    /**
     * Static methods to be called after project function checks have completed
     *
     * Called after function calls to functions defined in the project.
     *
     * Allows influencing the return type and adding of modifications.
     *
     * @var list<class-string<AfterFunctionCallAnalysisInterface>>
     */
    public $after_function_checks = [];
    /** @var list<class-string<LegacyAfterFunctionCallAnalysisInterface>> */
    public $legacy_after_function_checks = [];

    /**
     * Static methods to be called after every function call
     *
     * Called after each function call, including php internal functions.
     *
     * Cannot change the call or influence its return type
     *
     * @var list<class-string<AfterEveryFunctionCallAnalysisInterface>>
     */
    public $after_every_function_checks = [];
    /** @var list<class-string<LegacyAfterEveryFunctionCallAnalysisInterface>> */
    public $legacy_after_every_function_checks = [];

    /**
     * Static methods to be called after expression checks have completed
     *
     * @var list<class-string<AfterExpressionAnalysisInterface>>
     */
    public $after_expression_checks = [];
    /** @var list<class-string<LegacyAfterExpressionAnalysisInterface>> */
    public $legacy_after_expression_checks = [];

    /**
     * Static methods to be called after statement checks have completed
     *
     * @var list<class-string<AfterStatementAnalysisInterface>>
     */
    public $after_statement_checks = [];
    /** @var list<class-string<LegacyAfterStatementAnalysisInterface>> */
    public $legacy_after_statement_checks = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var list<class-string<StringInterpreterInterface>>
     */
    public $string_interpreters = [];
    /** @var list<class-string<LegacyStringInterpreterInterface>> */
    public $legacy_string_interpreters = [];

    /**
     * Static methods to be called after classlike exists checks have completed
     *
     * @var list<class-string<AfterClassLikeExistenceCheckInterface>>
     */
    public $after_classlike_exists_checks = [];
    /** @var list<class-string<LegacyAfterClassLikeExistenceCheckInterface>> */
    public $legacy_after_classlike_exists_checks = [];

    /**
     * Static methods to be called after classlike checks have completed
     *
     * @var list<class-string<AfterClassLikeAnalysisInterface>>
     */
    public $after_classlike_checks = [];
    /** @var list<class-string<LegacyAfterClassLikeAnalysisInterface>> */
    public $legacy_after_classlike_checks = [];

    /**
     * Static methods to be called after classlikes have been scanned
     *
     * @var list<class-string<AfterClassLikeVisitInterface>>
     */
    private $after_visit_classlikes = [];
    /** @var list<class-string<LegacyAfterClassLikeVisitInterface>> */
    private $legacy_after_visit_classlikes = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var list<class-string<AfterCodebasePopulatedInterface>>
     */
    public $after_codebase_populated = [];
    /** @var list<class-string<LegacyAfterCodebasePopulatedInterface>> */
    public $legacy_after_codebase_populated = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var list<class-string<AfterAnalysisInterface>>
     */
    public $after_analysis = [];
    /** @var list<class-string<LegacyAfterAnalysisInterface>> */
    public $legacy_after_analysis = [];

    /**
     * Static methods to be called after a file has been analyzed
     *
     * @var list<class-string<AfterFileAnalysisInterface>>
     */
    public $after_file_checks = [];
    /** @var list<class-string<LegacyAfterFileAnalysisInterface>> */
    public $legacy_after_file_checks = [];

    /**
     * Static methods to be called before a file is analyzed
     *
     * @var list<class-string<BeforeFileAnalysisInterface>>
     */
    public $before_file_checks = [];
    /** @var list<class-string<LegacyBeforeFileAnalysisInterface>> */
    public $legacy_before_file_checks = [];

    /**
     * Static methods to be called after functionlike checks have completed
     *
     * @var list<class-string<AfterFunctionLikeAnalysisInterface>>
     */
    public $after_functionlike_checks = [];
    /** @var list<class-string<LegacyAfterFunctionLikeAnalysisInterface>> */
    public $legacy_after_functionlike_checks = [];

    /**
     * Static methods to be called to see if taints should be added
     *
     * @var list<class-string<AddTaintsInterface>>
     */
    public $add_taints_checks = [];

    /**
     * Static methods to be called to see if taints should be removed
     *
     * @var list<class-string<RemoveTaintsInterface>>
     */
    public $remove_taints_checks = [];

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyAfterMethodCallAnalysisInterface::class)) {
            $this->legacy_after_method_checks[] = $class;
        } elseif (is_subclass_of($class, AfterMethodCallAnalysisInterface::class)) {
            $this->after_method_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterFunctionCallAnalysisInterface::class)) {
            $this->legacy_after_function_checks[] = $class;
        } elseif (is_subclass_of($class, AfterFunctionCallAnalysisInterface::class)) {
            $this->after_function_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterEveryFunctionCallAnalysisInterface::class)) {
            $this->legacy_after_every_function_checks[] = $class;
        } elseif (is_subclass_of($class, AfterEveryFunctionCallAnalysisInterface::class)) {
            $this->after_every_function_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterExpressionAnalysisInterface::class)) {
            $this->legacy_after_expression_checks[] = $class;
        } elseif (is_subclass_of($class, AfterExpressionAnalysisInterface::class)) {
            $this->after_expression_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterStatementAnalysisInterface::class)) {
            $this->legacy_after_statement_checks[] = $class;
        } elseif (is_subclass_of($class, AfterStatementAnalysisInterface::class)) {
            $this->after_statement_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyStringInterpreterInterface::class)) {
            $this->legacy_string_interpreters[] = $class;
        } elseif (is_subclass_of($class, StringInterpreterInterface::class)) {
            $this->string_interpreters[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterClassLikeExistenceCheckInterface::class)) {
            $this->legacy_after_classlike_exists_checks[] = $class;
        } elseif (is_subclass_of($class, AfterClassLikeExistenceCheckInterface::class)) {
            $this->after_classlike_exists_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterClassLikeAnalysisInterface::class)) {
            $this->legacy_after_classlike_checks[] = $class;
        } elseif (is_subclass_of($class, AfterClassLikeAnalysisInterface::class)) {
            $this->after_classlike_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterClassLikeVisitInterface::class)) {
            $this->legacy_after_visit_classlikes[] = $class;
        } elseif (is_subclass_of($class, AfterClassLikeVisitInterface::class)) {
            $this->after_visit_classlikes[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterCodebasePopulatedInterface::class)) {
            $this->legacy_after_codebase_populated[] = $class;
        } elseif (is_subclass_of($class, AfterCodebasePopulatedInterface::class)) {
            $this->after_codebase_populated[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterAnalysisInterface::class)) {
            $this->legacy_after_analysis[] = $class;
        } elseif (is_subclass_of($class, AfterAnalysisInterface::class)) {
            $this->after_analysis[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterFileAnalysisInterface::class)) {
            $this->legacy_after_file_checks[] = $class;
        } elseif (is_subclass_of($class, AfterFileAnalysisInterface::class)) {
            $this->after_file_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyBeforeFileAnalysisInterface::class)) {
            $this->legacy_before_file_checks[] = $class;
        } elseif (is_subclass_of($class, BeforeFileAnalysisInterface::class)) {
            $this->before_file_checks[] = $class;
        }

        if (is_subclass_of($class, LegacyAfterFunctionLikeAnalysisInterface::class)) {
            $this->legacy_after_functionlike_checks[] = $class;
        } elseif (is_subclass_of($class, AfterFunctionLikeAnalysisInterface::class)) {
            $this->after_functionlike_checks[] = $class;
        }

        if (is_subclass_of($class, AddTaintsInterface::class)) {
            $this->add_taints_checks[] = $class;
        }

        if (is_subclass_of($class, RemoveTaintsInterface::class)) {
            $this->remove_taints_checks[] = $class;
        }
    }

    public function hasAfterMethodCallAnalysisHandlers(): bool
    {
        return count($this->after_method_checks) || count($this->legacy_after_method_checks);
    }

    public function dispatchAfterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        foreach ($this->after_method_checks as $handler) {
            $handler::afterMethodCallAnalysis($event);
        }

        foreach ($this->legacy_after_method_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $return_type_candidate = $event->getReturnTypeCandidate();
            $handler::afterMethodCallAnalysis(
                $event->getExpr(),
                $event->getMethodId(),
                $event->getAppearingMethodId(),
                $event->getDeclaringMethodId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements,
                $return_type_candidate
            );
            $event->setFileReplacements($file_replacements);
            $event->setReturnTypeCandidate($return_type_candidate);
        }
    }

    public function dispatchAfterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void
    {
        foreach ($this->after_function_checks as $handler) {
            $handler::afterFunctionCallAnalysis($event);
        }

        foreach ($this->legacy_after_function_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterFunctionCallAnalysis(
                $event->getExpr(),
                $event->getFunctionId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $event->getReturnTypeCandidate(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void
    {
        foreach ($this->after_every_function_checks as $handler) {
            $handler::afterEveryFunctionCallAnalysis($event);
        }

        foreach ($this->legacy_after_every_function_checks as $handler) {
            $handler::afterEveryFunctionCallAnalysis(
                $event->getExpr(),
                $event->getFunctionId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        foreach ($this->after_expression_checks as $handler) {
            if ($handler::afterExpressionAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_expression_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterExpressionAnalysis(
                $event->getExpr(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function dispatchAfterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool
    {
        foreach ($this->after_statement_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_statement_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function dispatchStringInterpreter(StringInterpreterEvent $event): ?TLiteralString
    {
        foreach ($this->string_interpreters as $handler) {
            if ($type = $handler::getTypeFromValue($event)) {
                return $type;
            }
        }

        foreach ($this->legacy_string_interpreters as $handler) {
            if ($type = $handler::getTypeFromValue($event->getValue())) {
                return $type;
            }
        }

        return null;
    }

    public function dispatchAfterClassLikeExistenceCheck(AfterClassLikeExistenceCheckEvent $event): void
    {
        foreach ($this->after_classlike_exists_checks as $handler) {
            $handler::afterClassLikeExistenceCheck($event);
        }

        foreach ($this->legacy_after_classlike_exists_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterClassLikeExistenceCheck(
                $event->getFqClassName(),
                $event->getCodeLocation(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterClassLikeAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        foreach ($this->after_classlike_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_classlike_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getClasslikeStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function hasAfterClassLikeVisitHandlers(): bool
    {
        return count($this->after_visit_classlikes) || count($this->legacy_after_visit_classlikes);
    }

    public function dispatchAfterClassLikeVisit(AfterClassLikeVisitEvent $event): void
    {
        foreach ($this->after_visit_classlikes as $handler) {
            $handler::afterClassLikeVisit($event);
        }

        foreach ($this->legacy_after_visit_classlikes as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterClassLikeVisit(
                $event->getStmt(),
                $event->getStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        foreach ($this->after_codebase_populated as $handler) {
            $handler::afterCodebasePopulated($event);
        }

        foreach ($this->legacy_after_codebase_populated as $handler) {
            $handler::afterCodebasePopulated(
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterAnalysis(AfterAnalysisEvent $event): void
    {
        foreach ($this->after_analysis as $handler) {
            $handler::afterAnalysis($event);
        }

        foreach ($this->legacy_after_analysis as $handler) {
            /** @psalm-suppress MixedArgumentTypeCoercion due to Psalm bug */
            $handler::afterAnalysis(
                $event->getCodebase(),
                $event->getIssues(),
                $event->getBuildInfo(),
                $event->getSourceControlInfo()
            );
        }
    }

    public function dispatchAfterFileAnalysis(AfterFileAnalysisEvent $event): void
    {
        foreach ($this->after_file_checks as $handler) {
            $handler::afterAnalyzeFile($event);
        }

        foreach ($this->legacy_after_file_checks as $handler) {
            $handler::afterAnalyzeFile(
                $event->getStatementsSource(),
                $event->getFileContext(),
                $event->getFileStorage(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchBeforeFileAnalysis(BeforeFileAnalysisEvent $event): void
    {
        foreach ($this->before_file_checks as $handler) {
            $handler::beforeAnalyzeFile($event);
        }

        foreach ($this->legacy_before_file_checks as $handler) {
            $handler::beforeAnalyzeFile(
                $event->getStatementsSource(),
                $event->getFileContext(),
                $event->getFileStorage(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterFunctionLikeAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        foreach ($this->after_functionlike_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_functionlike_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getFunctionlikeStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function dispatchAddTaints(AddRemoveTaintsEvent $event): array
    {
        $added_taints = [];

        foreach ($this->add_taints_checks as $handler) {
            $added_taints = array_merge($added_taints, $handler::addTaints($event));
        }

        return $added_taints;
    }

    /**
     * @return list<string>
     */
    public function dispatchRemoveTaints(AddRemoveTaintsEvent $event): array
    {
        $removed_taints = [];

        foreach ($this->remove_taints_checks as $handler) {
            $removed_taints = array_merge($removed_taints, $handler::removeTaints($event));
        }

        return $removed_taints;
    }
}
