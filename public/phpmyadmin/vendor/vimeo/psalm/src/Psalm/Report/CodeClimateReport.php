<?php

namespace Psalm\Report;

use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Json\Json;
use Psalm\Report;

use function array_map;
use function array_values;
use function md5;

/**
 * CodeClimate format
 * This is the format used by Gitlab for CodeQuality
 *
 * @see https://docs.gitlab.com/ee/user/project/merge_requests/code_quality.html
 * @see https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md#data-types
 *
 * @author Olivier Doucet <webmaster@ajeux.com>
 */
class CodeClimateReport extends Report
{
    public function create(): string
    {
        $options = $this->pretty ? Json::PRETTY : Json::DEFAULT;

        $issues_data = array_map(
            function (IssueData $issue): array {
                /**
                 * map fields to new structure.
                 * Expected fields:
                 * - type
                 * - check_name
                 * - description*
                 * - content
                 * - categories[]
                 * - severity
                 * - fingerprint*
                 * - location.path*
                 * - location.lines.begin*
                 *
                 * Fields with * are the one used by Gitlab for Code Quality
                 */
                return [
                    'type' => 'issue',
                    'check_name' => $issue->type,
                    'description' => $issue->message,
                    'categories' => [$issue->type],
                    'severity' => $this->convertSeverity($issue->severity),
                    'fingerprint' => $this->calculateFingerprint($issue),
                    'location' => [
                        'path' => $issue->file_name,
                        'lines' => [
                            'begin' => $issue->line_from,
                            'end' => $issue->line_to,
                        ],
                    ],
                ];
            },
            $this->issues_data
        );

        return Json::encode(array_values($issues_data), $options) . "\n";
    }

    /**
     * convert our own severity to CodeClimate format
     * Values can be : info, minor, major, critical, or blocker
     */
    protected function convertSeverity(string $input): string
    {
        if (Config::REPORT_INFO === $input) {
            return 'info';
        }
        if (Config::REPORT_ERROR === $input) {
            return 'critical';
        }
        if (Config::REPORT_SUPPRESS === $input) {
            return 'minor';
        }

        // unknown cases ? fallback
        return 'critical';
    }

    /**
     * calculate a unique fingerprint for a given issue
     */
    protected function calculateFingerprint(IssueData $issue): string
    {
        return md5($issue->type.$issue->message.$issue->file_name.$issue->from.$issue->to);
    }
}
