<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use function array_combine;
use function array_merge;
use function file_get_contents;
use function intval;
use function is_array;
use function is_readable;
use function mb_strpos;
use function mb_substr;
use function preg_match_all;
use function preg_split;

/**
 * Linux based SysInfo class
 */
class Linux extends Base
{
    /**
     * The OS name
     *
     * @var string
     */
    public $os = 'Linux';

    /**
     * Gets load information
     *
     * @return array<string, int> with load data
     */
    public function loadavg()
    {
        $buf = file_get_contents('/proc/stat');
        if ($buf === false) {
            $buf = '';
        }

        $pos = mb_strpos($buf, "\n");
        if ($pos === false) {
            $pos = 0;
        }

        $nums = preg_split(
            '/\s+/',
            mb_substr(
                $buf,
                0,
                $pos
            )
        );

        if (! is_array($nums)) {
            return ['busy' => 0, 'idle' => 0];
        }

        return [
            'busy' => (int) $nums[1] + (int) $nums[2] + (int) $nums[3],
            'idle' => (int) $nums[4],
        ];
    }

    /**
     * Checks whether class is supported in this environment
     */
    public function supported(): bool
    {
        return @is_readable('/proc/meminfo') && @is_readable('/proc/stat');
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        $content = @file_get_contents('/proc/meminfo');
        if ($content === false) {
            return [];
        }

        preg_match_all(SysInfo::MEMORY_REGEXP, $content, $matches);

        /** @var array<string, int>|false $mem */
        $mem = array_combine($matches[1], $matches[2]);
        if ($mem === false) {
            return [];
        }

        $defaults = [
            'MemTotal' => 0,
            'MemFree' => 0,
            'Cached' => 0,
            'Buffers' => 0,
            'SwapTotal' => 0,
            'SwapFree' => 0,
            'SwapCached' => 0,
        ];

        $mem = array_merge($defaults, $mem);

        foreach ($mem as $idx => $value) {
            $mem[$idx] = intval($value);
        }

        $mem['MemUsed'] = $mem['MemTotal'] - $mem['MemFree'] - $mem['Cached'] - $mem['Buffers'];
        $mem['SwapUsed'] = $mem['SwapTotal'] - $mem['SwapFree'] - $mem['SwapCached'];

        return $mem;
    }
}
