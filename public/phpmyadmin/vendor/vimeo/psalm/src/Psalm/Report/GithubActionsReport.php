<?php

namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;

use function sprintf;
use function strtr;

class GithubActionsReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $issue_reference = $issue_data->link ? ' (see ' . $issue_data->link . ')' : '';
            $properties = sprintf(
                'file=%1$s,line=%2$d,col=%3$d,title=%4$s',
                $this->escapeProperty($issue_data->file_name),
                $this->escapeProperty($issue_data->line_from),
                $this->escapeProperty($issue_data->column_from),
                $this->escapeProperty($issue_data->type)
            );

            $data = $this->escapeData(sprintf(
                '%1$s:%2$d:%3$d: %4$s: %5$s',
                $issue_data->file_name,
                $issue_data->line_from,
                $issue_data->column_from,
                $issue_data->type,
                $issue_data->message . $issue_reference
            ));

            $output .= sprintf(
                '::%1$s %2$s::%3$s',
                ($issue_data->severity === Config::REPORT_ERROR ? 'error' : 'warning'),
                $properties,
                $data
            ) . "\n";
        }

        return $output;
    }

    private function escapeData(string $data): string
    {
        return strtr(
            $data,
            [
                '%' => '%25',
                "\r" => '%0D',
                "\n" => '%0A',
            ]
        );
    }

    /** @param mixed $value */
    private function escapeProperty($value): string
    {
        return strtr(
            (string) $value,
            [
                '%' => '%25',
                "\r" => '%0D',
                "\n" => '%0A',
                ':' => '%3A',
                ',' => '%2C',
            ]
        );
    }
}
