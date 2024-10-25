<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalMethod;
use Psalm\IssueBuffer;

class MethodCallProhibitionAnalyzer
{
    /**
     * @param  string[]     $suppressed_issues
     *
     * @return false|null
     */
    public static function analyze(
        Codebase $codebase,
        Context $context,
        MethodIdentifier $method_id,
        ?string $caller_identifier,
        CodeLocation $code_location,
        array $suppressed_issues
    ): ?bool {
        $codebase_methods = $codebase->methods;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location,
                    (string) $method_id
                ),
                $suppressed_issues
            );
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
        ) {
            if (!NamespaceAnalyzer::isWithinAny($caller_identifier ?? "", $storage->internal)) {
                IssueBuffer::maybeAdd(
                    new InternalMethod(
                        'The method ' . $codebase_methods->getCasedMethodId($method_id)
                            . ' is internal to ' . InternalClass::listToPhrase($storage->internal)
                            . ' but called from ' . ($caller_identifier ?: 'root namespace'),
                        $code_location,
                        (string) $method_id
                    ),
                    $suppressed_issues
                );
            }
        }
        return null;
    }
}
