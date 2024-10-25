<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Issue\TaintedCallable;
use Psalm\Issue\TaintedCookie;
use Psalm\Issue\TaintedCustom;
use Psalm\Issue\TaintedEval;
use Psalm\Issue\TaintedFile;
use Psalm\Issue\TaintedHeader;
use Psalm\Issue\TaintedHtml;
use Psalm\Issue\TaintedInclude;
use Psalm\Issue\TaintedLdap;
use Psalm\Issue\TaintedSSRF;
use Psalm\Issue\TaintedShell;
use Psalm\Issue\TaintedSql;
use Psalm\Issue\TaintedSystemSecret;
use Psalm\Issue\TaintedTextWithQuotes;
use Psalm\Issue\TaintedUnserialize;
use Psalm\Issue\TaintedUserSecret;
use Psalm\IssueBuffer;
use Psalm\Type\TaintKind;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_merge;
use function array_unique;
use function count;
use function end;
use function implode;
use function json_encode;
use function ksort;
use function sort;
use function strlen;
use function substr;

class TaintFlowGraph extends DataFlowGraph
{
    /** @var array<string, TaintSource> */
    private $sources = [];

    /** @var array<string, DataFlowNode> */
    private $nodes = [];

    /** @var array<string, TaintSink> */
    private $sinks = [];

    /** @var array<string, array<string, true>> */
    private $specialized_calls = [];

    /** @var array<string, array<string, true>> */
    private $specializations = [];

    public function addNode(DataFlowNode $node): void
    {
        $this->nodes[$node->id] = $node;

        if ($node->unspecialized_id && $node->specialization_key) {
            $this->specialized_calls[$node->specialization_key][$node->unspecialized_id] = true;
            $this->specializations[$node->unspecialized_id][$node->specialization_key] = true;
        }
    }

    public function addSource(TaintSource $node): void
    {
        $this->sources[$node->id] = $node;
    }

    public function addSink(TaintSink $node): void
    {
        $this->sinks[$node->id] = $node;
        // in the rare case the sink is the _next_ node, this is necessary
        $this->nodes[$node->id] = $node;
    }

    public function addGraph(self $taint): void
    {
        $this->sources += $taint->sources;
        $this->sinks += $taint->sinks;
        $this->nodes += $taint->nodes;
        $this->specialized_calls += $taint->specialized_calls;

        foreach ($taint->forward_edges as $key => $map) {
            if (!isset($this->forward_edges[$key])) {
                $this->forward_edges[$key] = $map;
            } else {
                $this->forward_edges[$key] += $map;
            }
        }

        foreach ($taint->specializations as $key => $map) {
            if (!isset($this->specializations[$key])) {
                $this->specializations[$key] = $map;
            } else {
                $this->specializations[$key] += $map;
            }
        }
    }

    public function getPredecessorPath(DataFlowNode $source): string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getShortSummary();
        }

        $source_descriptor = $source->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $previous_source = $source->previous;

        if ($previous_source) {
            if ($previous_source === $source) {
                return '';
            }

            if ($source->code_location
                && $previous_source->code_location
                && $previous_source->code_location->getHash() === $source->code_location->getHash()
                && $previous_source->previous
            ) {
                return $this->getPredecessorPath($previous_source->previous) . ' -> ' . $source_descriptor;
            }

            return $this->getPredecessorPath($previous_source) . ' -> ' . $source_descriptor;
        }

        return $source_descriptor;
    }

    public function getSuccessorPath(DataFlowNode $sink): string
    {
        $location_summary = '';

        if ($sink->code_location) {
            $location_summary = $sink->code_location->getShortSummary();
        }

        $sink_descriptor = $sink->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $next_sink = $sink->previous;

        if ($next_sink) {
            if ($next_sink === $sink) {
                return '';
            }

            if ($sink->code_location
                && $next_sink->code_location
                && $next_sink->code_location->getHash() === $sink->code_location->getHash()
                && $next_sink->previous
            ) {
                return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink->previous);
            }

            return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink);
        }

        return $sink_descriptor;
    }

    /**
     * @return list<array{location: ?CodeLocation, label: string, entry_path_type: string}>
     */
    public function getIssueTrace(DataFlowNode $source): array
    {
        $previous_source = $source->previous;

        $node = [
            'location' => $source->code_location,
            'label' => $source->label,
            'entry_path_type' => end($source->path_types) ?: ''
        ];

        if ($previous_source) {
            if ($previous_source === $source) {
                return [];
            }

            return array_merge($this->getIssueTrace($previous_source), [$node]);
        }

        return [$node];
    }

    public function connectSinksAndSources(): void
    {
        $visited_source_ids = [];

        $sources = $this->sources;
        $sinks = $this->sinks;

        ksort($this->specializations);
        ksort($this->forward_edges);

        // reprocess resolved descendants up to a maximum nesting level of 40
        for ($i = 0; count($sinks) && count($sources) && $i < 40; $i++) {
            $new_sources = [];

            ksort($sources);

            foreach ($sources as $source) {
                $source_taints = $source->taints;
                sort($source_taints);

                $visited_source_ids[$source->id][implode(',', $source_taints)] = true;

                $generated_sources = $this->getSpecializedSources($source);

                foreach ($generated_sources as $generated_source) {
                    $new_sources = array_merge(
                        $new_sources,
                        $this->getChildNodes(
                            $generated_source,
                            $source_taints,
                            $sinks,
                            $visited_source_ids
                        )
                    );
                }
            }

            $sources = $new_sources;
        }
    }

    /**
     * @param array<string> $source_taints
     * @param array<DataFlowNode> $sinks
     * @return array<string, DataFlowNode>
     */
    private function getChildNodes(
        DataFlowNode $generated_source,
        array $source_taints,
        array $sinks,
        array $visited_source_ids
    ): array {
        $new_sources = [];

        $config = Config::getInstance();

        $project_analyzer = ProjectAnalyzer::getInstance();

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            $path_type = $path->type;
            $added_taints = $path->unescaped_taints ?: [];
            $removed_taints = $path->escaped_taints ?: [];

            if (!isset($this->nodes[$to_id])) {
                continue;
            }

            $destination_node = $this->nodes[$to_id];

            $new_taints = array_unique(
                array_diff(
                    array_merge($source_taints, $added_taints),
                    $removed_taints
                )
            );

            sort($new_taints);

            if (isset($visited_source_ids[$to_id][implode(',', $new_taints)])) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arraykey', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arrayvalue', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'property', $generated_source->path_types)) {
                continue;
            }

            if ($generated_source->code_location
                && $project_analyzer->canReportIssues($generated_source->code_location->file_path)
                && !$config->reportIssueInFile('TaintedInput', $generated_source->code_location->file_path)
            ) {
                continue;
            }

            if (isset($sinks[$to_id])) {
                $matching_taints = array_intersect($sinks[$to_id]->taints, $new_taints);

                if ($matching_taints && $generated_source->code_location) {
                    if ($sinks[$to_id]->code_location
                        && $config->reportIssueInFile('TaintedInput', $sinks[$to_id]->code_location->file_path)
                    ) {
                        $issue_location = $sinks[$to_id]->code_location;
                    } else {
                        $issue_location = $generated_source->code_location;
                    }

                    $issue_trace = $this->getIssueTrace($generated_source);
                    $path = $this->getPredecessorPath($generated_source)
                        . ' -> ' . $this->getSuccessorPath($sinks[$to_id]);

                    foreach ($matching_taints as $matching_taint) {
                        switch ($matching_taint) {
                            case TaintKind::INPUT_CALLABLE:
                                $issue = new TaintedCallable(
                                    'Detected tainted text',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_UNSERIALIZE:
                                $issue = new TaintedUnserialize(
                                    'Detected tainted code passed to unserialize or similar',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_INCLUDE:
                                $issue = new TaintedInclude(
                                    'Detected tainted code passed to include or similar',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_EVAL:
                                $issue = new TaintedEval(
                                    'Detected tainted code passed to eval or similar',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_SQL:
                                $issue = new TaintedSql(
                                    'Detected tainted SQL',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_HTML:
                                $issue = new TaintedHtml(
                                    'Detected tainted HTML',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_HAS_QUOTES:
                                $issue = new TaintedTextWithQuotes(
                                    'Detected tainted text with possible quotes',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_SHELL:
                                $issue = new TaintedShell(
                                    'Detected tainted shell code',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::USER_SECRET:
                                $issue = new TaintedUserSecret(
                                    'Detected tainted user secret leaking',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::SYSTEM_SECRET:
                                $issue = new TaintedSystemSecret(
                                    'Detected tainted system secret leaking',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_SSRF:
                                $issue = new TaintedSSRF(
                                    'Detected tainted network request',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_LDAP:
                                $issue = new TaintedLdap(
                                    'Detected tainted LDAP request',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_COOKIE:
                                $issue = new TaintedCookie(
                                    'Detected tainted cookie',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_FILE:
                                $issue = new TaintedFile(
                                    'Detected tainted file handling',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            case TaintKind::INPUT_HEADER:
                                $issue = new TaintedHeader(
                                    'Detected tainted header',
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                                break;

                            default:
                                $issue = new TaintedCustom(
                                    'Detected tainted ' . $matching_taint,
                                    $issue_location,
                                    $issue_trace,
                                    $path
                                );
                        }

                        IssueBuffer::maybeAdd($issue);
                    }
                }
            }

            $new_destination = clone $destination_node;
            $new_destination->previous = $generated_source;
            $new_destination->taints = $new_taints;
            $new_destination->specialized_calls = $generated_source->specialized_calls;
            $new_destination->path_types = array_merge($generated_source->path_types, [$path_type]);

            $key = $to_id .
                ' ' . json_encode($new_destination->specialized_calls) .
                ' ' . json_encode($new_destination->taints);
            $new_sources[$key] = $new_destination;
        }

        return $new_sources;
    }

    /** @return array<int, DataFlowNode> */
    private function getSpecializedSources(DataFlowNode $source): array
    {
        $generated_sources = [];

        if (isset($this->forward_edges[$source->id])) {
            return [$source];
        }

        if ($source->specialization_key && isset($this->specialized_calls[$source->specialization_key])) {
            $generated_source = clone $source;

            $generated_source->id = substr($source->id, 0, -strlen($source->specialization_key) - 1);

            $generated_source->specialized_calls[$source->specialization_key][$generated_source->id] = true;

            $generated_sources[] = $generated_source;
        } elseif (isset($this->specializations[$source->id])) {
            foreach ($this->specializations[$source->id] as $specialization => $_) {
                if (!$source->specialized_calls || isset($source->specialized_calls[$specialization])) {
                    $new_source = clone $source;

                    $new_source->id = $source->id . '-' . $specialization;

                    unset($new_source->specialized_calls[$specialization]);

                    $generated_sources[] = $new_source;
                }
            }
        } else {
            foreach ($source->specialized_calls as $key => $map) {
                if (isset($map[$source->id]) && isset($this->forward_edges[$source->id . '-' . $key])) {
                    $new_source = clone $source;

                    $new_source->id = $source->id . '-' . $key;

                    $generated_sources[] = $new_source;
                }
            }
        }

        return array_filter(
            $generated_sources,
            function ($new_source): bool {
                return isset($this->forward_edges[$new_source->id]);
            }
        );
    }
}
