<?php

declare(strict_types=1);

namespace Psalm\Report;

use Psalm\Report;

use function array_key_exists;
use function uksort;

class CountReport extends Report
{
    public function create(): string
    {
        $issue_type_counts = [];
        foreach ($this->issues_data as $issue_data) {
            if (array_key_exists($issue_data->type, $issue_type_counts)) {
                $issue_type_counts[$issue_data->type]++;
            } else {
                $issue_type_counts[$issue_data->type] = 1;
            }
        }
        uksort($issue_type_counts, function (string $a, string $b) use ($issue_type_counts): int {
            $cmp_result = $issue_type_counts[$a] <=> $issue_type_counts[$b];
            if ($cmp_result === 0) {
                return $a <=> $b;
            } else {
                return $cmp_result;
            }
        });

        $output = '';
        foreach ($issue_type_counts as $issue_type => $count) {
            $output .= "{$issue_type}: {$count}\n";
        }
        return $output;
    }
}
