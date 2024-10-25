<?php

namespace Psalm\Report;

use Psalm\Config;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report;

use function basename;
use function get_cfg_var;
use function ini_get;
use function strlen;
use function strtr;
use function substr;

class ConsoleReport extends Report
{
    /** @var string|null */
    private $link_format;

    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->format($issue_data) . "\n" . "\n";
        }

        return $output;
    }

    private function format(IssueData $issue_data): string
    {
        $issue_string = '';

        $is_error = $issue_data->severity === Config::REPORT_ERROR;

        if ($is_error) {
            $issue_string .= ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_reference = $issue_data->link ? ' (see ' . $issue_data->link . ')' : '';

        $issue_string .= ': ' . $issue_data->type
            . ' - ' . $this->getFileReference($issue_data)
            . ' - ' . $issue_data->message . $issue_reference . "\n";


        if ($issue_data->taint_trace) {
            $issue_string .= $this->getTaintSnippets($issue_data->taint_trace);
        } elseif ($this->show_snippet) {
            $snippet = $issue_data->snippet;

            if (!$this->use_color) {
                $issue_string .= $snippet;
            } else {
                $selection_start = $issue_data->from - $issue_data->snippet_from;
                $selection_length = $issue_data->to - $issue_data->from;

                $issue_string .= substr($snippet, 0, $selection_start)
                    . ($is_error ? "\e[97;41m" : "\e[30;47m") . substr($snippet, $selection_start, $selection_length)
                    . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n";
            }
        }

        if ($issue_data->other_references) {
            if ($this->show_snippet) {
                $issue_string .= "\n";
            }

            $issue_string .= $this->getTaintSnippets($issue_data->other_references);
        }

        return $issue_string;
    }

    /**
     * @param non-empty-list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     */
    private function getTaintSnippets(array $taint_trace): string
    {
        $snippets = '';

        foreach ($taint_trace as $node_data) {
            if ($node_data instanceof DataFlowNodeData) {
                $snippets .= '  ' . $node_data->label . ' - ' . $this->getFileReference($node_data) . "\n";

                if ($this->show_snippet) {
                    $snippet = $node_data->snippet;

                    if (!$this->use_color) {
                        $snippets .= $snippet . "\n\n";
                    } else {
                        $selection_start = $node_data->from - $node_data->snippet_from;
                        $selection_length = $node_data->to - $node_data->from;

                        $snippets .= substr($snippet, 0, $selection_start)
                            . "\e[30;47m" . substr($snippet, $selection_start, $selection_length)
                            . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n\n";
                    }
                }
            } else {
                $snippets .= '  ' . $node_data['label'] . "\n";
                $snippets .= '    <no known location>' . "\n\n";
            }
        }

        return $snippets;
    }

    /**
     * @param IssueData|DataFlowNodeData $data
     */
    private function getFileReference($data): string
    {
        $reference = $data->file_name . ':' . $data->line_from . ':' . $data->column_from;

        if (!$this->use_color) {
            return $reference;
        }

        $file_basename = basename($data->file_name);
        $file_path = substr($data->file_name, 0, -strlen($file_basename));

        $reference = $file_path
            . "\033[1;31m"
            . $file_basename . ':' . $data->line_from . ':' . $data->column_from
            . "\033[0m"
        ;

        if ($this->in_ci) {
            return $reference;
        }

        if (null === $this->link_format) {
            // if xdebug is not enabled, use `get_cfg_var` to get the value directly from php.ini
            $this->link_format = ini_get('xdebug.file_link_format') ?: get_cfg_var('xdebug.file_link_format')
                ?: 'file://%f#L%l';
        }

        $link = strtr($this->link_format, ['%f' => $data->file_path, '%l' => $data->line_from]);
        // $reference = $data->file_name . ':' . $data->line_from . ':' . $data->column_from;


        return "\033]8;;" . $link . "\033\\" . $reference . "\033]8;;\033\\";
    }
}
