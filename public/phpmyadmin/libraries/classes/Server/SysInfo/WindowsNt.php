<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use COM;

use function class_exists;
use function count;
use function in_array;
use function is_string;
use function trim;

/**
 * Windows NT based SysInfo class
 */
class WindowsNt extends Base
{
    /** @var COM|null */
    private $wmi;

    /**
     * The OS name
     *
     * @var string
     */
    public $os = 'WINNT';

    /**
     * Constructor to access to wmi database.
     */
    public function __construct()
    {
        if (! class_exists('COM')) {
            $this->wmi = null;

            return;
        }

        // initialize the wmi object
        $objLocator = new COM('WbemScripting.SWbemLocator');
        $this->wmi = $objLocator->ConnectServer();
    }

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        $sum = 0;
        $buffer = $this->getWMI('Win32_Processor', ['LoadPercentage']);

        foreach ($buffer as $load) {
            $value = $load['LoadPercentage'];
            $sum += $value;
        }

        return ['loadavg' => $sum / count($buffer)];
    }

    /**
     * Checks whether class is supported in this environment
     */
    public function supported(): bool
    {
        return $this->wmi !== null;
    }

    /**
     * Reads data from WMI
     *
     * @param string $strClass Class to read
     * @param array  $strValue Values to read
     *
     * @return array with results
     */
    private function getWMI($strClass, array $strValue = [])
    {
        $arrData = [];

        $objWEBM = $this->wmi->Get($strClass);
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $arrProp = $objWEBM->Properties_;
        $arrWEBMCol = $objWEBM->Instances_();
        foreach ($arrWEBMCol as $objItem) {
            $arrInstance = [];
            foreach ($arrProp as $propItem) {
                // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                $name = $propItem->Name;
                if (! empty($strValue) && ! in_array($name, $strValue)) {
                    continue;
                }

                $value = $objItem->$name;
                if (is_string($value)) {
                    $arrInstance[$name] = trim($value);
                } else {
                    $arrInstance[$name] = $value;
                }
            }

            $arrData[] = $arrInstance;
        }

        return $arrData;
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        $buffer = $this->getWMI(
            'Win32_OperatingSystem',
            [
                'TotalVisibleMemorySize',
                'FreePhysicalMemory',
            ]
        );
        $mem = [];
        $mem['MemTotal'] = $buffer[0]['TotalVisibleMemorySize'];
        $mem['MemFree'] = $buffer[0]['FreePhysicalMemory'];
        $mem['MemUsed'] = $mem['MemTotal'] - $mem['MemFree'];

        $buffer = $this->getWMI('Win32_PageFileUsage');

        $mem['SwapTotal'] = 0;
        $mem['SwapFree'] = 0;
        $mem['SwapUsed'] = 0;
        $mem['SwapPeak'] = 0;

        foreach ($buffer as $swapdevice) {
            $mem['SwapTotal'] += $swapdevice['AllocatedBaseSize'] * 1024;
            $mem['SwapUsed'] += $swapdevice['CurrentUsage'] * 1024;
            $mem['SwapPeak'] += $swapdevice['PeakUsage'] * 1024;
        }

        $mem['SwapFree'] = $mem['SwapTotal'] - $mem['SwapUsed'];

        return $mem;
    }
}
