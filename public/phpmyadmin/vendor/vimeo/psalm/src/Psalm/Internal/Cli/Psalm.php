<?php

namespace Psalm\Internal\Cli;

use Composer\Autoload\ClassLoader;
use Psalm\Config;
use Psalm\Config\Creator;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigCreationException;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Codebase\ReferenceMapGenerator;
use Psalm\Internal\Composer;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Fork\Pool;
use Psalm\Internal\Fork\PsalmRestarter;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\IssueBuffer;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\DefaultProgress;
use Psalm\Progress\LongProgress;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use RuntimeException;
use Webmozart\PathUtil\Path;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_slice;
use function array_sum;
use function array_values;
use function chdir;
use function count;
use function file_exists;
use function file_put_contents;
use function fwrite;
use function gc_collect_cycles;
use function gc_disable;
use function getcwd;
use function getenv;
use function getopt;
use function implode;
use function in_array;
use function ini_get;
use function ini_set;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function json_encode;
use function max;
use function microtime;
use function preg_match;
use function preg_replace;
use function realpath;
use function setlocale;
use function str_repeat;
use function strpos;
use function substr;
use function version_compare;

use const DIRECTORY_SEPARATOR;
use const LC_CTYPE;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../../IssueBuffer.php';

final class Psalm
{
    private const SHORT_OPTIONS = [
        'f:',
        'm',
        'h',
        'v',
        'c:',
        'i',
        'r:',
    ];

    private const LONG_OPTIONS = [
        'clear-cache',
        'clear-global-cache',
        'config:',
        'debug',
        'debug-by-line',
        'debug-performance',
        'debug-emitted-issues',
        'diff',
        'disable-extension:',
        'find-dead-code::',
        'find-unused-code::',
        'find-unused-variables',
        'find-references-to:',
        'help',
        'ignore-baseline',
        'init',
        'memory-limit:',
        'monochrome',
        'no-diff',
        'no-cache',
        'no-reflection-cache',
        'no-file-cache',
        'output-format:',
        'plugin:',
        'report:',
        'report-show-info:',
        'root:',
        'set-baseline:',
        'show-info:',
        'show-snippet:',
        'stats',
        'threads:',
        'update-baseline',
        'use-baseline:',
        'use-ini-defaults',
        'version',
        'php-version:',
        'generate-json-map:',
        'generate-stubs:',
        'alter',
        'language-server',
        'refactor',
        'shepherd::',
        'no-progress',
        'long-progress',
        'no-suggestions',
        'include-php-versions', // used for baseline
        'pretty-print', // used for JSON reports
        'track-tainted-input',
        'taint-analysis',
        'security-analysis',
        'dump-taint-graph:',
        'find-unused-psalm-suppress',
        'error-level:',
    ];

    /**
     * @param array<int,string> $argv
     */
    public static function run(array $argv): void
    {
        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install();

        $args = array_slice($argv, 1);

        // get options from command line
        $options = getopt(implode('', self::SHORT_OPTIONS), self::LONG_OPTIONS);
        if (false === $options) {
            throw new RuntimeException('Failed to parse CLI options');
        }

        self::forwardCliCall($options, $argv);

        self::validateCliArguments($args);

        self::setMemoryLimit($options);

        self::syncShortOptions($options);

        if (isset($options['c']) && is_array($options['c'])) {
            fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
            exit(1);
        }


        if (array_key_exists('h', $options)) {
            echo CliUtils::getPsalmHelpText();
            /*
            --shepherd[=host]
                Send data to Shepherd, Psalm's GitHub integration tool.
                `host` is the location of the Shepherd server. It defaults to shepherd.dev
                More information is available at https://psalm.dev/shepherd
            */

            exit;
        }

        $current_dir = self::getCurrentDir($options);

        $path_to_config = CliUtils::getPathToConfig($options);

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        // capture environment before registering autoloader (it may destroy it)
        IssueBuffer::captureServer($_SERVER);

        $include_collector = new IncludeCollector();
        $first_autoloader = $include_collector->runAndCollect(
            // we ignore the FQN because of a hack in scoper.inc that needs full path
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            function () use ($current_dir, $options, $vendor_dir): ?\Composer\Autoload\ClassLoader {
                return CliUtils::requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);
            }
        );

        $run_taint_analysis = self::shouldRunTaintAnalysis($options);

        if (array_key_exists('v', $options)) {
            echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
            exit;
        }

        $output_format = self::initOutputFormat($options);

        [$config, $init_source_dir] = self::initConfig(
            $current_dir,
            $args,
            $vendor_dir,
            $first_autoloader,
            $path_to_config,
            $output_format,
            $run_taint_analysis,
            $options
        );

        $config->setIncludeCollector($include_collector);

        $in_ci = CliUtils::runningInCI();        // disable progressbar on CI

        if ($in_ci) {
            $options['long-progress'] = true;
        }

        $threads = self::detectThreads($options, $config, $in_ci);

        self::emitMacPcreWarning($options, $threads);

        self::restart($options, $config, $threads);

        if (isset($options['debug-emitted-issues'])) {
            $config->debug_emitted_issues = true;
        }


        setlocale(LC_CTYPE, 'C');

        if (isset($options['set-baseline'])) {
            if (is_array($options['set-baseline'])) {
                die('Only one baseline file can be created at a time' . PHP_EOL);
            }
        }

        $paths_to_check = CliUtils::getPathsToCheck($options['f'] ?? null);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        $plugins = [];

        if (isset($options['plugin'])) {
            $plugins = $options['plugin'];

            if (!is_array($plugins)) {
                $plugins = [$plugins];
            }
        }

        $show_info = self::initShowInfo($options);

        $is_diff = self::initIsDiff($options);

        $find_unused_code = self::shouldFindUnusedCode($options, $config);

        $find_unused_variables = isset($options['find-unused-variables']);

        $find_references_to = isset($options['find-references-to']) && is_string($options['find-references-to'])
            ? $options['find-references-to']
            : null;

        if (isset($options['shepherd']) || getenv('PSALM_SHEPHERD')) {
            if (isset($options['shepherd'])) {
                if (is_string($options['shepherd'])) {
                    $config->shepherd_host = $options['shepherd'];
                }
            } elseif (getenv('PSALM_SHEPHERD')) {
                if (false !== ($shepherd_host = getenv('PSALM_SHEPHERD_HOST'))) {
                    $config->shepherd_host = $shepherd_host;
                }
            }
            $shepherd_plugin = Path::canonicalize(__DIR__ . '/../../Plugin/Shepherd.php');

            if (!file_exists($shepherd_plugin)) {
                die('Could not find Shepherd plugin location ' . $shepherd_plugin . PHP_EOL);
            }

            $plugins[] = $shepherd_plugin;
        }

        if (isset($options['clear-cache'])) {
            self::clearCache($config);
        }

        if (isset($options['clear-global-cache'])) {
            self::clearGlobalCache($config);
        }

        $progress = self::initProgress($options, $config);
        $providers = self::initProviders($options, $config, $current_dir);

        $stdout_report_options = self::initStdoutReportOptions($options, $show_info, $output_format, $in_ci);

        /** @var list<string>|string $report_file_paths type guaranteed by argument to getopt() */
        $report_file_paths = $options['report'] ?? [];
        if (is_string($report_file_paths)) {
            $report_file_paths = [$report_file_paths];
        }

        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            $stdout_report_options,
            ProjectAnalyzer::getFileReportOptions(
                $report_file_paths,
                isset($options['report-show-info'])
                    ? $options['report-show-info'] !== 'false' && $options['report-show-info'] !== '0'
                    : true
            ),
            $threads,
            $progress
        );

        CliUtils::initPhpVersion($options, $config, $project_analyzer);

        $start_time = microtime(true);

        self::configureProjectAnalyzer(
            $options,
            $config,
            $project_analyzer,
            $find_references_to,
            $find_unused_code,
            $find_unused_variables,
            $run_taint_analysis
        );

        if ($config->run_taint_analysis || $run_taint_analysis) {
            $is_diff = false;
        }

        /** @var string $plugin_path */
        foreach ($plugins as $plugin_path) {
            $config->addPluginPath($plugin_path);
        }

        if ($paths_to_check === null) {
            $project_analyzer->check($current_dir, $is_diff);
        } elseif ($paths_to_check) {
            $project_analyzer->checkPaths($paths_to_check);
        }

        if ($find_references_to) {
            $project_analyzer->findReferencesTo($find_references_to);
        }

        self::storeFlowGraph($options, $project_analyzer);

        if (isset($options['generate-json-map']) && is_string($options['generate-json-map'])) {
            self::storeTypeMap($providers, $config, $options['generate-json-map']);
        }

        if (isset($options['generate-stubs'])) {
            self::generateStubs($options, $providers, $project_analyzer);
        }

        if (!isset($options['i'])) {
            IssueBuffer::finish(
                $project_analyzer,
                !$paths_to_check,
                $start_time,
                isset($options['stats']),
                self::initBaseline($options, $config, $current_dir, $path_to_config)
            );
        } else {
            self::autoGenerateConfig($project_analyzer, $current_dir, $init_source_dir, $vendor_dir);
        }
    }

    private static function initOutputFormat(array $options): string
    {
        return isset($options['output-format']) && is_string($options['output-format'])
            ? $options['output-format']
            : Report::TYPE_CONSOLE;
    }

    private static function initShowInfo(array $options): bool
    {
        return isset($options['show-info'])
            ? $options['show-info'] === 'true' || $options['show-info'] === '1'
            : false;
    }

    private static function initIsDiff(array $options): bool
    {
        return !isset($options['no-diff'])
            && !isset($options['set-baseline'])
            && !isset($options['update-baseline']);
    }

    /**
     * @param array<int,string> $args
     */
    private static function validateCliArguments(array $args): void
    {
        array_map(
            function (string $arg): void {
                if (strpos($arg, '--') === 0 && $arg !== '--') {
                    $arg_name = preg_replace('/=.*$/', '', substr($arg, 2), 1);

                    if (!in_array($arg_name, self::LONG_OPTIONS)
                        && !in_array($arg_name . ':', self::LONG_OPTIONS)
                        && !in_array($arg_name . '::', self::LONG_OPTIONS)
                    ) {
                        fwrite(
                            STDERR,
                            'Unrecognised argument "--' . $arg_name . '"' . PHP_EOL
                            . 'Type --help to see a list of supported arguments'. PHP_EOL
                        );
                        exit(1);
                    }
                } elseif (strpos($arg, '-') === 0 && $arg !== '-' && $arg !== '--') {
                    $arg_name = preg_replace('/=.*$/', '', substr($arg, 1));

                    if (!in_array($arg_name, self::SHORT_OPTIONS)
                        && !in_array($arg_name . ':', self::SHORT_OPTIONS)
                    ) {
                        fwrite(
                            STDERR,
                            'Unrecognised argument "-' . $arg_name . '"' . PHP_EOL
                            . 'Type --help to see a list of supported arguments'. PHP_EOL
                        );
                        exit(1);
                    }
                }
            },
            $args
        );
    }

    /**
     * @param array<string,string|false|list<mixed>> $options
     */
    private static function setMemoryLimit(array $options): void
    {
        if (!array_key_exists('use-ini-defaults', $options)) {
            ini_set('display_errors', 'stderr');
            ini_set('display_startup_errors', '1');

            $memoryLimit = (8 * 1024 * 1024 * 1024);

            if (array_key_exists('memory-limit', $options)) {
                $memoryLimit = $options['memory-limit'];

                if (!is_scalar($memoryLimit)) {
                    throw new ConfigException('Invalid memory limit specified.');
                }
            }

            ini_set('memory_limit', (string) $memoryLimit);
        }
    }

    /**
     * @param array<int, string> $args
     */
    private static function generateConfig(string $current_dir, array &$args): void
    {
        if (file_exists($current_dir . 'psalm.xml')) {
            die('A config file already exists in the current directory' . PHP_EOL);
        }

        $args = array_values(array_filter(
            $args,
            function (string $arg): bool {
                return $arg !== '--ansi'
                    && $arg !== '--no-ansi'
                    && $arg !== '-i'
                    && $arg !== '--init'
                    && $arg !== '--debug'
                    && $arg !== '--debug-by-line'
                    && $arg !== '--debug-emitted-issues'
                    && strpos($arg, '--disable-extension=') !== 0
                    && strpos($arg, '--root=') !== 0
                    && strpos($arg, '--r=') !== 0;
            }
        ));

        $init_level = null;
        $init_source_dir = null;
        if (count($args)) {
            if (count($args) > 2) {
                die('Too many arguments provided for psalm --init' . PHP_EOL);
            }

            if (isset($args[1])) {
                if (!preg_match('/^[1-8]$/', $args[1])) {
                    die('Config strictness must be a number between 1 and 8 inclusive' . PHP_EOL);
                }

                $init_level = (int)$args[1];
            }

            $init_source_dir = $args[0];
        }

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        if (null !== $init_level) {
            try {
                $template_contents = Creator::getContents(
                    $current_dir,
                    $init_source_dir,
                    $init_level,
                    $vendor_dir
                );
            } catch (ConfigCreationException $e) {
                die($e->getMessage() . PHP_EOL);
            }

            if (!file_put_contents($current_dir . 'psalm.xml', $template_contents)) {
                die('Could not write to psalm.xml' . PHP_EOL);
            }

            exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
        }
    }

    private static function loadConfig(
        ?string $path_to_config,
        string $current_dir,
        string $output_format,
        ?ClassLoader $first_autoloader,
        bool $run_taint_analysis,
        array $options
    ): Config {
        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            $output_format,
            $first_autoloader,
            $run_taint_analysis
        );

        if (isset($options['error-level'])
            && is_numeric($options['error-level'])
        ) {
            $config_level = (int) $options['error-level'];

            if (!in_array($config_level, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                throw new ConfigException(
                    'Invalid error level ' . $config_level
                );
            }

            $config->level = $config_level;
        }
        return $config;
    }

    private static function initProgress(array $options, Config $config): Progress
    {
        $debug = array_key_exists('debug', $options) || array_key_exists('debug-by-line', $options);

        $show_info = isset($options['show-info'])
            ? $options['show-info'] === 'true' || $options['show-info'] === '1'
            : false;

        if ($debug) {
            $progress = new DebugProgress();
        } elseif (isset($options['no-progress'])) {
            $progress = new VoidProgress();
        } else {
            $show_errors = !$config->error_baseline || isset($options['ignore-baseline']);
            if (isset($options['long-progress'])) {
                $progress = new LongProgress($show_errors, $show_info);
            } else {
                $progress = new DefaultProgress($show_errors, $show_info);
            }
        }
        return $progress;
    }

    private static function initProviders(array $options, Config $config, string $current_dir): Providers
    {
        if (isset($options['no-cache']) || isset($options['i'])) {
            $providers = new Providers(
                new FileProvider
            );
        } else {
            $no_reflection_cache = isset($options['no-reflection-cache']);
            $no_file_cache = isset($options['no-file-cache']);

            $file_storage_cache_provider = $no_reflection_cache
                ? null
                : new FileStorageCacheProvider($config);

            $classlike_storage_cache_provider = $no_reflection_cache
                ? null
                : new ClassLikeStorageCacheProvider($config);

            $providers = new Providers(
                new FileProvider,
                new ParserCacheProvider($config, !$no_file_cache),
                $file_storage_cache_provider,
                $classlike_storage_cache_provider,
                new FileReferenceCacheProvider($config),
                new ProjectCacheProvider(Composer::getLockFilePath($current_dir))
            );
        }
        return $providers;
    }

    /**
     * @param array{set-baseline:string} $options
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function generateBaseline(
        array $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config
    ): array {
        fwrite(STDERR, 'Writing error baseline to file...' . PHP_EOL);

        try {
            $issue_baseline = ErrorBaseline::read(
                new FileProvider,
                $options['set-baseline']
            );
        } catch (ConfigException $e) {
            $issue_baseline = [];
        }

        ErrorBaseline::create(
            new FileProvider,
            $options['set-baseline'],
            IssueBuffer::getIssuesData(),
            $config->include_php_versions_in_error_baseline || isset($options['include-php-versions'])
        );

        fwrite(STDERR, "Baseline saved to {$options['set-baseline']}.");

        CliUtils::updateConfigFile(
            $config,
            $path_to_config ?? $current_dir,
            $options['set-baseline']
        );

        fwrite(STDERR, PHP_EOL);

        return $issue_baseline;
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function updateBaseline(array $options, Config $config): array
    {
        $baselineFile = $config->error_baseline;

        if (empty($baselineFile)) {
            die('Cannot update baseline, because no baseline file is configured.' . PHP_EOL);
        }

        try {
            $issue_current_baseline = ErrorBaseline::read(
                new FileProvider,
                $baselineFile
            );
            $total_issues_current_baseline = ErrorBaseline::countTotalIssues($issue_current_baseline);

            $issue_baseline = ErrorBaseline::update(
                new FileProvider,
                $baselineFile,
                IssueBuffer::getIssuesData(),
                $config->include_php_versions_in_error_baseline || isset($options['include-php-versions'])
            );
            $total_issues_updated_baseline = ErrorBaseline::countTotalIssues($issue_baseline);

            $total_fixed_issues = $total_issues_current_baseline - $total_issues_updated_baseline;

            if ($total_fixed_issues > 0) {
                echo str_repeat('-', 30) . "\n";
                echo $total_fixed_issues . ' errors fixed' . "\n";
            }
        } catch (ConfigException $exception) {
            fwrite(STDERR, 'Could not update baseline file: ' . $exception->getMessage() . PHP_EOL);
            exit(1);
        }

        return $issue_baseline;
    }

    private static function storeTypeMap(Providers $providers, Config $config, string $type_map_location): void
    {
        $file_map = $providers->file_reference_provider->getFileMaps();

        $name_file_map = [];

        $expected_references = [];

        foreach ($file_map as $file_path => $map) {
            $file_name = $config->shortenFileName($file_path);
            foreach ($map[0] as $map_parts) {
                $expected_references[$map_parts[1]] = true;
            }
            $map[2] = [];
            $name_file_map[$file_name] = $map;
        }

        $reference_dictionary = ReferenceMapGenerator::getReferenceMap(
            $providers->classlike_storage_provider,
            $expected_references
        );

        $type_map_string = json_encode(['files' => $name_file_map, 'references' => $reference_dictionary]);

        $providers->file_provider->setContents(
            $type_map_location,
            $type_map_string
        );
    }

    private static function autoGenerateConfig(
        ProjectAnalyzer $project_analyzer,
        string $current_dir,
        ?string $init_source_dir,
        string $vendor_dir
    ): void {
        $issues_by_file = IssueBuffer::getIssuesData();

        if (!$issues_by_file) {
            $init_level = 1;
        } else {
            $codebase = $project_analyzer->getCodebase();
            $mixed_counts = $codebase->analyzer->getTotalTypeCoverage($codebase);

            $init_level = Creator::getLevel(
                array_merge(...array_values($issues_by_file)),
                array_sum($mixed_counts)
            );
        }

        echo "\n" . 'Detected level ' . $init_level . ' as a suitable initial default' . "\n";

        try {
            $template_contents = Creator::getContents(
                $current_dir,
                $init_source_dir,
                $init_level,
                $vendor_dir
            );
        } catch (ConfigCreationException $e) {
            die($e->getMessage() . PHP_EOL);
        }

        if (!file_put_contents($current_dir . 'psalm.xml', $template_contents)) {
            die('Could not write to psalm.xml' . PHP_EOL);
        }

        exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
    }

    private static function initStdoutReportOptions(
        array $options,
        bool $show_info,
        string $output_format,
        bool $in_ci
    ): ReportOptions {
        $stdout_report_options = new ReportOptions();
        $stdout_report_options->use_color = !array_key_exists('m', $options);
        $stdout_report_options->show_info = $show_info;
        $stdout_report_options->show_suggestions = !array_key_exists('no-suggestions', $options);
        /**
         * @psalm-suppress PropertyTypeCoercion
         */
        $stdout_report_options->format = $output_format;
        $stdout_report_options->show_snippet = !isset($options['show-snippet']) || $options['show-snippet'] !== "false";
        $stdout_report_options->pretty = isset($options['pretty-print']) && $options['pretty-print'] !== "false";
        $stdout_report_options->in_ci = $in_ci;

        return $stdout_report_options;
    }

    /** @return never */
    private static function clearGlobalCache(Config $config): void
    {
        $cache_directory = $config->getGlobalCacheDirectory();

        if ($cache_directory) {
            Config::removeCacheDirectory($cache_directory);
            echo 'Global cache directory deleted' . PHP_EOL;
        }

        exit;
    }

    /** @return never */
    private static function clearCache(Config $config): void
    {
        $cache_directory = $config->getCacheDirectory();

        if ($cache_directory !== null) {
            Config::removeCacheDirectory($cache_directory);
        }
        echo 'Cache directory deleted' . PHP_EOL;
        exit;
    }

    private static function getCurrentDir(array $options): string
    {
        $cwd = getcwd();
        if (false === $cwd) {
            fwrite(STDERR, 'Cannot get current working directory' . PHP_EOL);
            exit(1);
        }

        $current_dir = $cwd . DIRECTORY_SEPARATOR;

        if (isset($options['r']) && is_string($options['r'])) {
            $root_path = realpath($options['r']);

            if (!$root_path) {
                fwrite(
                    STDERR,
                    'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL
                );
                exit(1);
            }

            $current_dir = $root_path . DIRECTORY_SEPARATOR;
        }

        return $current_dir;
    }

    private static function emitMacPcreWarning(array $options, int $threads): void
    {
        if (!isset($options['threads'])
            && !isset($options['debug'])
            && $threads === 1
            && ini_get('pcre.jit') === '1'
            && PHP_OS === 'Darwin'
            && version_compare(PHP_VERSION, '7.3.0') >= 0
            && version_compare(PHP_VERSION, '7.4.0') < 0
        ) {
            echo(
                'If you want to run Psalm as a language server, or run Psalm with' . PHP_EOL
                    . 'multiple processes (--threads=4), beware:' . PHP_EOL
                    . Pool::MAC_PCRE_MESSAGE . PHP_EOL . PHP_EOL
            );
        }
    }

    private static function restart(array $options, Config $config, int $threads): void
    {
        $ini_handler = new PsalmRestarter('PSALM');

        if (isset($options['disable-extension'])) {
            if (is_array($options['disable-extension'])) {
                /** @psalm-suppress MixedAssignment */
                foreach ($options['disable-extension'] as $extension) {
                    if (is_string($extension)) {
                        $ini_handler->disableExtension($extension);
                    }
                }
            } elseif (is_string($options['disable-extension'])) {
                $ini_handler->disableExtension($options['disable-extension']);
            }
        }

        if ($threads > 1) {
            $ini_handler->disableExtension('grpc');
        }

        $ini_handler->disableExtension('uopz');

        // If Xdebug is enabled, restart without it
        $ini_handler->check();

        if ($config->load_xdebug_stub === null && PsalmRestarter::getSkippedVersion() !== '') {
            $config->load_xdebug_stub = true;
        }
    }

    private static function detectThreads(array $options, Config $config, bool $in_ci): int
    {
        if (isset($options['threads'])) {
            $threads = (int)$options['threads'];
        } elseif (isset($options['debug']) || $in_ci) {
            $threads = 1;
        } elseif ($config->threads) {
            $threads = $config->threads;
        } else {
            $threads = max(1, ProjectAnalyzer::getCpuCount() - 1);
        }
        return $threads;
    }

    /** @psalm-suppress UnusedParam $argv is being reported as unused */
    private static function forwardCliCall(array $options, array $argv): void
    {
        if (isset($options['alter'])) {
            require_once __DIR__ . '/Psalter.php';
            Psalter::run($argv);
            exit;
        }

        if (isset($options['language-server'])) {
            require_once __DIR__ . '/LanguageServer.php';
            LanguageServer::run($argv);
            exit;
        }

        if (isset($options['refactor'])) {
            require_once __DIR__ . '/Refactor.php';
            Refactor::run($argv);
            exit;
        }
    }

    /**
     * @param array<string, false|list<mixed>|string> $options
     * @param-out array<string, false|list<mixed>|string> $options
     */
    private static function syncShortOptions(array &$options): void
    {
        if (array_key_exists('help', $options)) {
            $options['h'] = false;
        }

        if (array_key_exists('version', $options)) {
            $options['v'] = false;
        }

        if (array_key_exists('init', $options)) {
            $options['i'] = false;
        }

        if (array_key_exists('monochrome', $options)) {
            $options['m'] = false;
        }

        if (isset($options['config'])) {
            $options['c'] = $options['config'];
        }

        if (isset($options['root'])) {
            $options['r'] = $options['root'];
        }
    }

    /**
     * @param array<int, string> $args
     * @return array{Config,?string}
     */
    private static function initConfig(
        string $current_dir,
        array $args,
        string $vendor_dir,
        ?ClassLoader $first_autoloader,
        ?string $path_to_config,
        string $output_format,
        bool $run_taint_analysis,
        array $options
    ): array {
        $init_source_dir = null;
        if (isset($options['i'])) {
            self::generateConfig($current_dir, $args);
            // if we ever got here, it means we need to run Psalm once and generate the config
            // based on the errors we find
            $init_source_dir = $args[0] ?? null;

            echo "Calculating best config level based on project files\n";
            Creator::createBareConfig($current_dir, $init_source_dir, $vendor_dir);
            $config = Config::getInstance();
            $config->setComposerClassLoader($first_autoloader);
        } else {
            $config = self::loadConfig(
                $path_to_config,
                $current_dir,
                $output_format,
                $first_autoloader,
                $run_taint_analysis,
                $options
            );
        }
        return [$config, $init_source_dir];
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function initBaseline(
        array $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config
    ): array {
        $issue_baseline = [];

        if (isset($options['set-baseline']) && is_string($options['set-baseline'])) {
            $issue_baseline = self::generateBaseline($options, $config, $current_dir, $path_to_config);
        }

        if (isset($options['use-baseline'])) {
            if (!is_string($options['use-baseline'])) {
                fwrite(STDERR, '--use-baseline must be a string' . PHP_EOL);
                exit(1);
            }

            $baseline_file_path = $options['use-baseline'];
            $config->error_baseline = $baseline_file_path;
        } else {
            $baseline_file_path = $config->error_baseline;
        }

        if (isset($options['update-baseline'])) {
            $issue_baseline = self::updateBaseline($options, $config);
        }

        if (!$issue_baseline && $baseline_file_path && !isset($options['ignore-baseline'])) {
            try {
                $issue_baseline = ErrorBaseline::read(
                    new FileProvider,
                    $baseline_file_path
                );
            } catch (ConfigException $exception) {
                fwrite(STDERR, 'Error while reading baseline: ' . $exception->getMessage() . PHP_EOL);
                exit(1);
            }
        }

        return $issue_baseline;
    }

    private static function storeFlowGraph(array $options, ProjectAnalyzer $project_analyzer): void
    {
        /** @var string|null $dump_taint_graph */
        $dump_taint_graph = $options['dump-taint-graph'] ?? null;

        $flow_graph = $project_analyzer->getCodebase()->taint_flow_graph;
        if ($flow_graph !== null && $dump_taint_graph !== null) {
            file_put_contents($dump_taint_graph, "digraph Taints {\n\t".
                implode("\n\t", array_map(
                    function (array $edges) {
                        return '"'.implode('" -> "', $edges).'"';
                    },
                    $flow_graph->summarizeEdges()
                )) .
                "\n}\n");
        }
    }

    /** @return false|'always'|'auto' */
    private static function shouldFindUnusedCode(array $options, Config $config)
    {
        $find_unused_code = false;
        if (isset($options['find-dead-code'])) {
            $options['find-unused-code'] = $options['find-dead-code'] === 'always' ? 'always' : 'auto';
        }

        if (isset($options['find-unused-code'])) {
            if ($options['find-unused-code'] === 'always') {
                $find_unused_code = 'always';
            } else {
                $find_unused_code = 'auto';
            }
        }

        if ($config->find_unused_code) {
            $find_unused_code = 'auto';
        }

        return $find_unused_code;
    }

    private static function shouldRunTaintAnalysis(array $options): bool
    {
        return (isset($options['track-tainted-input'])
            || isset($options['security-analysis'])
            || isset($options['taint-analysis']));
    }

    /**
     * @param string|bool|null $find_references_to
     * @param false|'always'|'auto' $find_unused_code
     */
    private static function configureProjectAnalyzer(
        array $options,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        $find_references_to,
        $find_unused_code,
        bool $find_unused_variables,
        bool $run_taint_analysis
    ): void {
        if (isset($options['generate-json-map']) && is_string($options['generate-json-map'])) {
            $project_analyzer->getCodebase()->store_node_types = true;
        }

        if (array_key_exists('debug-by-line', $options)) {
            $project_analyzer->debug_lines = true;
        }

        if (array_key_exists('debug-performance', $options)) {
            $project_analyzer->debug_performance = true;
        }

        if ($find_references_to !== null) {
            $project_analyzer->getCodebase()->collectLocations();
            $project_analyzer->show_issues = false;
        }

        if ($find_unused_code) {
            $project_analyzer->getCodebase()->reportUnusedCode($find_unused_code);
        }

        if ($config->find_unused_variables || $find_unused_variables) {
            $project_analyzer->getCodebase()->reportUnusedVariables();
        }

        if ($config->run_taint_analysis || $run_taint_analysis) {
            $project_analyzer->trackTaintedInputs();
        }

        if ($config->find_unused_psalm_suppress || isset($options['find-unused-psalm-suppress'])) {
            $project_analyzer->trackUnusedSuppressions();
        }
    }

    private static function generateStubs(
        array $options,
        Providers $providers,
        ProjectAnalyzer $project_analyzer
    ): void {
        if (isset($options['generate-stubs']) && is_string($options['generate-stubs'])) {
            $stubs_location = $options['generate-stubs'];

            $providers->file_provider->setContents(
                $stubs_location,
                StubsGenerator::getAll(
                    $project_analyzer->getCodebase(),
                    $providers->classlike_storage_provider,
                    $providers->file_storage_provider
                )
            );
        }
    }
}
