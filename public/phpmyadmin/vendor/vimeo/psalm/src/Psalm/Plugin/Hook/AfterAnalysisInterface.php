<?php

namespace Psalm\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\SourceControl\SourceControlInfo;

/** @deprecated going to be removed in Psalm 5 */
interface AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        ?SourceControlInfo $source_control_info = null
    ): void;
}
