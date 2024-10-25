<?php

namespace Psalm;

use Psalm\CodeLocation\Raw;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\ExecutionEnvironment\BuildInfoCollector;
use Psalm\Internal\ExecutionEnvironment\GitInfoCollector;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\ConfigIssue;
use Psalm\Issue\MixedIssue;
use Psalm\Issue\TaintedInput;
use Psalm\Issue\UnusedPsalmSuppress;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Report\CheckstyleReport;
use Psalm\Report\CodeClimateReport;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\CountReport;
use Psalm\Report\EmacsReport;
use Psalm\Report\GithubActionsReport;
use Psalm\Report\JsonReport;
use Psalm\Report\JsonSummaryReport;
use Psalm\Report\JunitReport;
use Psalm\Report\PhpStormReport;
use Psalm\Report\PylintReport;
use Psalm\Report\ReportOptions;
use Psalm\Report\SarifReport;
use Psalm\Report\SonarqubeReport;
use Psalm\Report\TextReport;
use Psalm\Report\XmlReport;
use RuntimeException;
use UnexpectedValueException;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_search;
use function array_splice;
use function array_sum;
use function array_values;
use function arsort;
use function count;
use function debug_print_backtrace;
use function dirname;
use function explode;
use function file_put_contents;
use function fwrite;
use function get_class;
use function implode;
use function in_array;
use function is_dir;
use function is_int;
use function ksort;
use function memory_get_peak_usage;
use function microtime;
use function mkdir;
use function number_format;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function round;
use function sha1;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function trim;
use function usort;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const PSALM_VERSION;
use const STDERR;

class IssueBuffer
{
    /**
     * @var array<string, list<IssueData>>
     */
    protected static $issues_data = [];

    /**
     * @var array<int, array>
     */
    protected static $console_issues = [];

    /**
     * @var array<string, int>
     */
    protected static $fixable_issue_counts = [];

    /**
     * @var int
     */
    protected static $error_count = 0;

    /**
     * @var array<string, bool>
     */
    protected static $emitted = [];

    /** @var int */
    protected static $recording_level = 0;

    /** @var array<int, array<int, CodeIssue>> */
    protected static $recorded_issues = [];

    /**
     * @var array<string, array<int, int>>
     */
    protected static $unused_suppressions = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected static $used_suppressions = [];

    /** @var array<array-key,mixed> */
    private static $server = [];

    /**
     * This will add an issue to be emitted if it's not suppressed and return if it has been added
     * @param string[]  $suppressed_issues
     */
    public static function accepts(CodeIssue $e, array $suppressed_issues = [], bool $is_fixable = false): bool
    {
        if (self::isSuppressed($e, $suppressed_issues)) {
            return false;
        }

        return self::add($e, $is_fixable);
    }

    /**
     * This will add an issue to be emitted if it's not suppressed
     * @param string[]  $suppressed_issues
     */
    public static function maybeAdd(CodeIssue $e, array $suppressed_issues = [], bool $is_fixable = false): void
    {
        if (self::isSuppressed($e, $suppressed_issues)) {
            return;
        }

        self::add($e, $is_fixable);
    }

    /**
     * This is part of the findUnusedPsalmSuppress feature
     */
    public static function addUnusedSuppression(string $file_path, int $offset, string $issue_type): void
    {
        if (strpos($issue_type, 'Tainted') === 0) {
            return;
        }

        if (isset(self::$used_suppressions[$file_path][$offset])) {
            return;
        }

        if (!isset(self::$unused_suppressions[$file_path])) {
            self::$unused_suppressions[$file_path] = [];
        }

        self::$unused_suppressions[$file_path][$offset] = $offset + strlen($issue_type) - 1;
    }

    /**
     * This will return false if an issue is ready to be added for emission. Reasons for not returning false include:
     * - The issue is suppressed in config
     * - We're in a recording state
     * - The issue is included in the list of issues to be suppressed in param
     * @param string[] $suppressed_issues
     */
    public static function isSuppressed(CodeIssue $e, array $suppressed_issues = []): bool
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);
        $file_path = $e->getFilePath();

        if (!$e instanceof ConfigIssue && !$config->reportIssueInFile($issue_type, $file_path)) {
            return true;
        }

        $suppressed_issue_position = array_search($issue_type, $suppressed_issues);

        if ($suppressed_issue_position !== false) {
            if (is_int($suppressed_issue_position)) {
                self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
            }

            return true;
        }

        $parent_issue_type = Config::getParentIssueType($issue_type);

        if ($parent_issue_type) {
            $suppressed_issue_position = array_search($parent_issue_type, $suppressed_issues);

            if ($suppressed_issue_position !== false) {
                if (is_int($suppressed_issue_position)) {
                    self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
                }

                return true;
            }
        }

        $suppress_all_position = $config->disable_suppress_all
            ? false
            : array_search('all', $suppressed_issues);

        if ($suppress_all_position !== false) {
            if (is_int($suppress_all_position)) {
                self::$used_suppressions[$file_path][$suppress_all_position] = true;
            }

            return true;
        }

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return true;
        }

        if ($e->code_location->getLineNumber() === -1) {
            return true;
        }

        if (self::$recording_level > 0) {
            self::$recorded_issues[self::$recording_level][] = $e;

            return true;
        }

        return false;
    }

    /**
     * Add an issue to be emitted. This method should normally not be used! Use IssueBuffer::maybeAdd instead.
     *
     * @psalm-internal Psalm\IssueBuffer
     * @psalm-internal Psalm\Type\Reconciler::getValueForKey
     *
     * @throws  CodeException
     */
    public static function add(CodeIssue $e, bool $is_fixable = false): bool
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $project_analyzer = ProjectAnalyzer::getInstance();

        if (!$project_analyzer->show_issues) {
            return false;
        }

        $is_tainted = strpos($issue_type, 'Tainted') === 0;

        if ($project_analyzer->getCodebase()->taint_flow_graph && !$is_tainted) {
            return false;
        }

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        if ($config->debug_emitted_issues) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = ob_get_clean();
            fwrite(STDERR, "\nEmitting {$e->getShortLocation()} $issue_type {$e->message}\n$trace\n");
        }

        // Make issue type for trace variable specific ("Trace" => "Trace~$var").
        $trace_var = $issue_type === 'Trace' && preg_match('/^(\$.+?):/', $e->message, $m) === 1 && isset($m[1])
            ? '~' . $m[1]
            : '';

        $emitted_key = $issue_type
            . $trace_var
            . '-' . $e->getShortLocation()
            . ':' . $e->code_location->getColumn()
            . ' ' . $e->dupe_key;

        if ($reporting_level === Config::REPORT_INFO) {
            if ($is_tainted || !self::alreadyEmitted($emitted_key)) {
                self::$issues_data[$e->getFilePath()][] = $e->toIssueData(Config::REPORT_INFO);

                if ($is_fixable) {
                    self::addFixableIssue($issue_type);
                }
            }

            return false;
        }

        if ($config->throw_exception) {
            FileAnalyzer::clearCache();

            $message = $e instanceof TaintedInput
                ? $e->getJourneyMessage()
                : ($e instanceof MixedIssue
                    ? $e->getMixedOriginMessage()
                    : $e->message);

            throw new CodeException(
                $issue_type
                    . ' - ' . $e->getShortLocationWithPrevious()
                    . ':' . $e->code_location->getColumn()
                    . ' - ' . $message
            );
        }

        if ($is_tainted || !self::alreadyEmitted($emitted_key)) {
            ++self::$error_count;
            self::$issues_data[$e->getFilePath()][] = $e->toIssueData(Config::REPORT_ERROR);

            if ($is_fixable) {
                self::addFixableIssue($issue_type);
            }
        }

        return true;
    }

    private static function removeRecordedIssue(string $issue_type, int $file_offset): void
    {
        $recorded_issues = self::$recorded_issues[self::$recording_level];
        $filtered_issues = [];

        foreach ($recorded_issues as $issue) {
            [$from] = $issue->code_location->getSelectionBounds();

            if ($issue::getIssueType() !== $issue_type || $from !== $file_offset) {
                $filtered_issues[] = $issue;
            }
        }

        self::$recorded_issues[self::$recording_level] = $filtered_issues;
    }

    /**
     * This will try to remove an issue that has been added for emission
     */
    public static function remove(string $file_path, string $issue_type, int $file_offset): void
    {
        if (self::$recording_level > 0) {
            self::removeRecordedIssue($issue_type, $file_offset);
        }

        if (!isset(self::$issues_data[$file_path])) {
            return;
        }

        $filtered_issues = [];

        foreach (self::$issues_data[$file_path] as $issue) {
            if ($issue->type !== $issue_type || $issue->from !== $file_offset) {
                $filtered_issues[] = $issue;
            }
        }

        if (empty($filtered_issues)) {
            unset(self::$issues_data[$file_path]);
        } else {
            self::$issues_data[$file_path] = $filtered_issues;
        }
    }

    public static function addFixableIssue(string $issue_type): void
    {
        if (isset(self::$fixable_issue_counts[$issue_type])) {
            self::$fixable_issue_counts[$issue_type]++;
        } else {
            self::$fixable_issue_counts[$issue_type] = 1;
        }
    }

    /**
     * @return array<string, list<IssueData>>
     */
    public static function getIssuesData(): array
    {
        return self::$issues_data;
    }

    /**
     * @return list<IssueData>
     */
    public static function getIssuesDataForFile(string $file_path): array
    {
        return self::$issues_data[$file_path] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public static function getFixableIssues(): array
    {
        return self::$fixable_issue_counts;
    }

    /**
     * @param array<string, int> $fixable_issue_counts
     */
    public static function addFixableIssues(array $fixable_issue_counts): void
    {
        foreach ($fixable_issue_counts as $issue_type => $count) {
            if (isset(self::$fixable_issue_counts[$issue_type])) {
                self::$fixable_issue_counts[$issue_type] += $count;
            } else {
                self::$fixable_issue_counts[$issue_type] = $count;
            }
        }
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function getUnusedSuppressions(): array
    {
        return self::$unused_suppressions;
    }

    /**
     * @return array<string, array<int, bool>>
     */
    public static function getUsedSuppressions(): array
    {
        return self::$used_suppressions;
    }

    /**
     * @param array<string, array<int, int>> $unused_suppressions
     */
    public static function addUnusedSuppressions(array $unused_suppressions): void
    {
        self::$unused_suppressions += $unused_suppressions;
    }

    /**
     * @param array<string, array<int, bool>> $used_suppressions
     */
    public static function addUsedSuppressions(array $used_suppressions): void
    {
        foreach ($used_suppressions as $file => $offsets) {
            if (!isset(self::$used_suppressions[$file])) {
                self::$used_suppressions[$file] = $offsets;
            } else {
                self::$used_suppressions[$file] += $offsets;
            }
        }
    }

    public static function processUnusedSuppressions(FileProvider $file_provider): void
    {
        $config = Config::getInstance();

        foreach (self::$unused_suppressions as $file_path => $offsets) {
            if (!$offsets) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                continue;
            }

            $file_contents = $file_provider->getContents($file_path);

            foreach ($offsets as $start => $end) {
                if (isset(self::$used_suppressions[$file_path][$start])) {
                    continue;
                }

                self::add(
                    new UnusedPsalmSuppress(
                        'This suppression is never used',
                        new Raw(
                            $file_contents,
                            $file_path,
                            $config->shortenFileName($file_path),
                            $start,
                            $end
                        )
                    )
                );
            }
        }
    }

    public static function getErrorCount(): int
    {
        return self::$error_count;
    }

    /**
     * @param array<string, list<IssueData>> $issues_data
     *
     */
    public static function addIssues(array $issues_data): void
    {
        foreach ($issues_data as $file_path => $file_issues) {
            foreach ($file_issues as $issue) {
                $emitted_key = $issue->type
                    . '-' . $issue->file_name
                    . ':' . $issue->line_from
                    . ':' . $issue->column_from
                    . ' ' . $issue->dupe_key;

                if (!self::alreadyEmitted($emitted_key)) {
                    self::$issues_data[$file_path][] = $issue;
                }
            }
        }
    }

    /**
     * @param  array<string,array<string,array{o:int, s:array<int, string>}>>  $issue_baseline
     *
     */
    public static function finish(
        ProjectAnalyzer $project_analyzer,
        bool $is_full,
        float $start_time,
        bool $add_stats = false,
        array $issue_baseline = []
    ): void {
        if (!$project_analyzer->stdout_report_options) {
            throw new UnexpectedValueException('Cannot finish without stdout report options');
        }

        $codebase = $project_analyzer->getCodebase();

        foreach ($codebase->config->config_issues as $issue) {
            if (self::accepts($issue)) {
                // fall through
            }
        }

        $error_count = 0;
        $info_count = 0;


        $issues_data = [];

        if (self::$issues_data) {
            if (in_array(
                $project_analyzer->stdout_report_options->format,
                [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM]
            )) {
                echo "\n";
            }

            ksort(self::$issues_data);

            foreach (self::$issues_data as $file_path => $file_issues) {
                usort(
                    $file_issues,
                    function (IssueData $d1, IssueData $d2): int {
                        if ($d1->file_path === $d2->file_path) {
                            if ($d1->line_from === $d2->line_from) {
                                if ($d1->column_from === $d2->column_from) {
                                    return 0;
                                }

                                return $d1->column_from > $d2->column_from ? 1 : -1;
                            }

                            return $d1->line_from > $d2->line_from ? 1 : -1;
                        }

                        return $d1->file_path > $d2->file_path ? 1 : -1;
                    }
                );
                self::$issues_data[$file_path] = $file_issues;
            }

            // make a copy so what gets saved in cache is unaffected by baseline
            $issues_data = self::$issues_data;

            if (!empty($issue_baseline)) {
                // Set severity for issues in baseline to INFO
                foreach ($issues_data as $file_path => $file_issues) {
                    foreach ($file_issues as $key => $issue_data) {
                        $file = $issue_data->file_name;
                        $file = str_replace('\\', '/', $file);
                        $type = $issue_data->type;

                        if (isset($issue_baseline[$file][$type]) && $issue_baseline[$file][$type]['o'] > 0) {
                            if ($issue_baseline[$file][$type]['o'] === count($issue_baseline[$file][$type]['s'])) {
                                $position = array_search(
                                    trim($issue_data->selected_text),
                                    $issue_baseline[$file][$type]['s'],
                                    true
                                );

                                if ($position !== false) {
                                    $issue_data->severity = Config::REPORT_INFO;
                                    array_splice($issue_baseline[$file][$type]['s'], $position, 1);
                                    $issue_baseline[$file][$type]['o']--;
                                }
                            } else {
                                $issue_baseline[$file][$type]['s'] = [];
                                $issue_data->severity = Config::REPORT_INFO;
                                $issue_baseline[$file][$type]['o']--;
                            }
                        }

                        $issues_data[$file_path][$key] = $issue_data;
                    }
                }
            }
        }

        echo self::getOutput(
            $issues_data,
            $project_analyzer->stdout_report_options,
            $codebase->analyzer->getTotalTypeCoverage($codebase)
        );

        foreach ($issues_data as $file_issues) {
            foreach ($file_issues as $issue_data) {
                if ($issue_data->severity === Config::REPORT_ERROR) {
                    ++$error_count;
                } else {
                    ++$info_count;
                }
            }
        }


        if ($codebase->config->eventDispatcher->after_analysis
            || $codebase->config->eventDispatcher->legacy_after_analysis
        ) {
            $source_control_info = null;
            $build_info = (new BuildInfoCollector(self::$server))->collect();

            try {
                $source_control_info = (new GitInfoCollector())->collect();
            } catch (RuntimeException $e) {
                // do nothing
            }

            /** @psalm-suppress ArgumentTypeCoercion due to Psalm bug */
            $event = new AfterAnalysisEvent(
                $codebase,
                $issues_data,
                $build_info,
                $source_control_info
            );

            $codebase->config->eventDispatcher->dispatchAfterAnalysis($event);
        }

        foreach ($project_analyzer->generated_report_options as $report_options) {
            if (!$report_options->output_path) {
                throw new UnexpectedValueException('Output path should not be null here');
            }

            $folder = dirname($report_options->output_path);
            if (!is_dir($folder) && !mkdir($folder, 0777, true) && !is_dir($folder)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            file_put_contents(
                $report_options->output_path,
                self::getOutput(
                    $issues_data,
                    $report_options,
                    $codebase->analyzer->getTotalTypeCoverage($codebase)
                )
            );
        }

        if (in_array(
            $project_analyzer->stdout_report_options->format,
            [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM]
        )) {
            echo str_repeat('-', 30) . "\n";

            if ($error_count) {
                echo($project_analyzer->stdout_report_options->use_color
                    ? "\e[0;31m" . $error_count . " errors\e[0m"
                    : $error_count . ' errors'
                ) . ' found' . "\n";
            } else {
                self::printSuccessMessage($project_analyzer);
            }

            $show_info = $project_analyzer->stdout_report_options->show_info;
            $show_suggestions = $project_analyzer->stdout_report_options->show_suggestions;

            if ($info_count && ($show_info || $show_suggestions)) {
                echo str_repeat('-', 30) . "\n";

                echo $info_count . ' other issues found.' . "\n";

                if (!$show_info) {
                    echo 'You can display them with ' .
                        ($project_analyzer->stdout_report_options->use_color
                            ? "\e[30;48;5;195m--show-info=true\e[0m"
                            : '--show-info=true') . "\n";
                }
            }

            if (self::$fixable_issue_counts && $show_suggestions && !$codebase->taint_flow_graph) {
                echo str_repeat('-', 30) . "\n";

                $total_count = array_sum(self::$fixable_issue_counts);
                $command = '--alter --issues=' . implode(',', array_keys(self::$fixable_issue_counts));
                $command .= ' --dry-run';

                echo 'Psalm can automatically fix ' . $total_count
                    . ($show_info ? ' issues' : ' of these issues') . ".\n"
                    . 'Run Psalm again with ' . "\n"
                    . ($project_analyzer->stdout_report_options->use_color
                        ? "\e[30;48;5;195m" . $command . "\e[0m"
                        : $command) . "\n"
                    . 'to see what it can fix.' . "\n";
            }

            echo str_repeat('-', 30) . "\n" . "\n";

            if ($start_time) {
                echo 'Checks took ' . number_format(microtime(true) - $start_time, 2) . ' seconds';
                echo ' and used ' . number_format(memory_get_peak_usage() / (1024 * 1024), 3) . 'MB of memory' . "\n";

                $analysis_summary = $codebase->analyzer->getTypeInferenceSummary($codebase);
                echo $analysis_summary . "\n";

                if ($add_stats) {
                    echo '-----------------' . "\n";
                    echo $codebase->analyzer->getNonMixedStats();
                    echo "\n";
                }

                if ($project_analyzer->debug_performance) {
                    echo '-----------------' . "\n";
                    echo 'Slow-to-analyze functions' . "\n";
                    echo '-----------------' . "\n\n";

                    $function_timings = $codebase->analyzer->getFunctionTimings();

                    arsort($function_timings);

                    $i = 0;

                    foreach ($function_timings as $function_id => $time) {
                        if (++$i > 10) {
                            break;
                        }

                        echo $function_id . ': ' . round(1000 * $time, 2) . 'ms per node' . "\n";
                    }

                    echo "\n";
                }
            }
        }

        if ($is_full && $start_time) {
            $codebase->file_reference_provider->removeDeletedFilesFromReferences();

            if ($project_analyzer->project_cache_provider) {
                $project_analyzer->project_cache_provider->processSuccessfulRun($start_time, PSALM_VERSION);
            }
        }

        if ($error_count
            && !($codebase->taint_flow_graph
                && $project_analyzer->generated_report_options
                && isset($_SERVER['GITHUB_WORKFLOW']))
        ) {
            exit(2);
        }
    }

    public static function printSuccessMessage(ProjectAnalyzer $project_analyzer): void
    {
        if (!$project_analyzer->stdout_report_options) {
            throw new UnexpectedValueException('Cannot print success message without stdout report options');
        }

        // this message will be printed
        $message = "No errors found!";

        // color block will contain this amount of characters
        $blockSize = 30;

        // message with prepended and appended whitespace to be same as $blockSize
        $messageWithPadding = str_repeat(' ', 7) . $message . str_repeat(' ', 7);

        // top side of the color block
        $paddingTop = str_repeat(' ', $blockSize);

        // bottom side of the color block
        $paddingBottom = str_repeat(' ', $blockSize);

        // background color, 42 = green
        $background = "42";

        // foreground/text color, 30 = black
        $foreground = "30";

        // text style, 1 = bold
        $style = "1";

        if ($project_analyzer->stdout_report_options->use_color) {
            echo "\e[{$background};{$style}m{$paddingTop}\e[0m" . "\n";
            echo "\e[{$background};{$foreground};{$style}m{$messageWithPadding}\e[0m" . "\n";
            echo "\e[{$background};{$style}m{$paddingBottom}\e[0m" . "\n";
        } else {
            echo "\n";
            echo "$messageWithPadding\n";
            echo "\n";
        }
    }

    /**
     * @param array<string, array<int, IssueData>> $issues_data
     * @param array{int, int} $mixed_counts
     *
     */
    public static function getOutput(
        array $issues_data,
        ReportOptions $report_options,
        array $mixed_counts = [0, 0]
    ): string {
        $total_expression_count = $mixed_counts[0] + $mixed_counts[1];
        $mixed_expression_count = $mixed_counts[0];

        $normalized_data = $issues_data === [] ? [] : array_merge(...array_values($issues_data));

        switch ($report_options->format) {
            case Report::TYPE_COMPACT:
                $output = new CompactReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_EMACS:
                $output = new EmacsReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_TEXT:
                $output = new TextReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JSON:
                $output = new JsonReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JSON_SUMMARY:
                $output = new JsonSummaryReport(
                    $normalized_data,
                    self::$fixable_issue_counts,
                    $report_options,
                    $mixed_expression_count,
                    $total_expression_count
                );
                break;

            case Report::TYPE_SONARQUBE:
                $output = new SonarqubeReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_PYLINT:
                $output = new PylintReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_CHECKSTYLE:
                $output = new CheckstyleReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_XML:
                $output = new XmlReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JUNIT:
                $output = new JunitReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_CONSOLE:
                $output = new ConsoleReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_GITHUB_ACTIONS:
                $output = new GithubActionsReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_PHP_STORM:
                $output = new PhpStormReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_SARIF:
                $output = new SarifReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_CODECLIMATE:
                $output = new CodeClimateReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_COUNT:
                $output = new CountReport($normalized_data, self::$fixable_issue_counts, $report_options);
                break;

            default:
                throw new RuntimeException('Unexpected report format: ' . $report_options->format);
        }

        return $output->create();
    }

    protected static function alreadyEmitted(string $message): bool
    {
        $sham = sha1($message);

        if (isset(self::$emitted[$sham])) {
            return true;
        }

        self::$emitted[$sham] = true;

        return false;
    }

    public static function clearCache(): void
    {
        self::$issues_data = [];
        self::$emitted = [];
        self::$error_count = 0;
        self::$recording_level = 0;
        self::$recorded_issues = [];
        self::$console_issues = [];
        self::$unused_suppressions = [];
        self::$used_suppressions = [];
    }

    /**
     * @return array<string, list<IssueData>>
     */
    public static function clear(): array
    {
        $current_data = self::$issues_data;
        self::$issues_data = [];
        self::$emitted = [];

        return $current_data;
    }

    /**
     * Return whether or not we're in a recording state regarding startRecording/stopRecording status
     */
    public static function isRecording(): bool
    {
        return self::$recording_level > 0;
    }

    /**
     * Increase the recording level in order to start recording issues instead of adding them while in a loop
     */
    public static function startRecording(): void
    {
        ++self::$recording_level;
        self::$recorded_issues[self::$recording_level] = [];
    }

    /**
     * Decrease the recording level after leaving a loop
     * @see startRecording
     */
    public static function stopRecording(): void
    {
        if (self::$recording_level === 0) {
            throw new UnexpectedValueException('Cannot stop recording - already at base level');
        }

        --self::$recording_level;
    }

    /**
     * This will return the recorded issues for the current recording level
     * @return array<int, CodeIssue>
     */
    public static function clearRecordingLevel(): array
    {
        if (self::$recording_level === 0) {
            throw new UnexpectedValueException('Not currently recording');
        }

        $recorded_issues = self::$recorded_issues[self::$recording_level];

        self::$recorded_issues[self::$recording_level] = [];

        return $recorded_issues;
    }

    /**
     * This will try to add issues that has been retrieved through clearRecordingLevel or record them at a lower level
     */
    public static function bubbleUp(CodeIssue $e): void
    {
        if (self::$recording_level === 0) {
            self::add($e);

            return;
        }

        self::$recorded_issues[self::$recording_level][] = $e;
    }

    /**
     * @internal
     * @param array<array-key,mixed> $server
     */
    final public static function captureServer(array $server): void
    {
        self::$server = $server;
    }
}
