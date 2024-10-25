<?php

namespace Psalm;

use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report\ReportOptions;

use function array_filter;
use function htmlspecialchars;

use const ENT_QUOTES;
use const ENT_XML1;

abstract class Report
{
    public const TYPE_COMPACT = 'compact';
    public const TYPE_CONSOLE = 'console';
    public const TYPE_PYLINT = 'pylint';
    public const TYPE_JSON = 'json';
    public const TYPE_JSON_SUMMARY = 'json-summary';
    public const TYPE_SONARQUBE = 'sonarqube';
    public const TYPE_EMACS = 'emacs';
    public const TYPE_XML = 'xml';
    public const TYPE_JUNIT = 'junit';
    public const TYPE_CHECKSTYLE = 'checkstyle';
    public const TYPE_TEXT = 'text';
    public const TYPE_GITHUB_ACTIONS = 'github';
    public const TYPE_PHP_STORM = 'phpstorm';
    public const TYPE_SARIF = 'sarif';
    public const TYPE_CODECLIMATE = 'codeclimate';
    public const TYPE_COUNT = 'count';

    /**
     * @var array<int, IssueData>
     */
    protected $issues_data;

    /** @var array<string, int> */
    protected $fixable_issue_counts;

    /** @var bool */
    protected $use_color;

    /** @var bool */
    protected $show_snippet;

    /** @var bool */
    protected $show_info;

    /** @var bool */
    protected $pretty;

    /** @var bool */
    protected $in_ci;

    /** @var int */
    protected $mixed_expression_count;

    /** @var int */
    protected $total_expression_count;

    /**
     * @param array<int, IssueData> $issues_data
     * @param array<string, int> $fixable_issue_counts
     */
    public function __construct(
        array $issues_data,
        array $fixable_issue_counts,
        ReportOptions $report_options,
        int $mixed_expression_count = 1,
        int $total_expression_count = 1
    ) {
        if (!$report_options->show_info) {
            $this->issues_data = array_filter(
                $issues_data,
                function ($issue_data): bool {
                    return $issue_data->severity !== Config::REPORT_INFO;
                }
            );
        } else {
            $this->issues_data = $issues_data;
        }
        $this->fixable_issue_counts = $fixable_issue_counts;

        $this->use_color = $report_options->use_color;
        $this->show_snippet = $report_options->show_snippet;
        $this->show_info = $report_options->show_info;
        $this->pretty = $report_options->pretty;
        $this->in_ci = $report_options->in_ci;

        $this->mixed_expression_count = $mixed_expression_count;
        $this->total_expression_count = $total_expression_count;
    }

    protected function xmlEncode(string $data): string
    {
        return htmlspecialchars($data, ENT_XML1 | ENT_QUOTES);
    }

    abstract public function create(): string;
}
