<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Server\SysInfo\SysInfo;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

use function __;
use function array_merge;
use function htmlspecialchars;
use function implode;
use function preg_match;
use function preg_replace_callback;
use function round;
use function sprintf;
use function str_contains;
use function substr;
use function vsprintf;

/**
 * A simple rules engine, that executes the rules in the advisory_rules files.
 */
class Advisor
{
    private const GENERIC_RULES_FILE = 'libraries/advisory_rules_generic.php';
    private const BEFORE_MYSQL80003_RULES_FILE = 'libraries/advisory_rules_mysql_before80003.php';

    /** @var DatabaseInterface */
    private $dbi;

    /** @var array */
    private $variables;

    /** @var array */
    private $globals;

    /** @var array */
    private $rules;

    /** @var array */
    private $runResult;

    /** @var ExpressionLanguage */
    private $expression;

    /**
     * @param DatabaseInterface  $dbi        DatabaseInterface object
     * @param ExpressionLanguage $expression ExpressionLanguage object
     */
    public function __construct(DatabaseInterface $dbi, ExpressionLanguage $expression)
    {
        $this->dbi = $dbi;
        $this->expression = $expression;
        /*
         * Register functions for ExpressionLanguage, we intentionally
         * do not implement support for compile as we do not use it.
         */
        $this->expression->register(
            'round',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param float $num
             */
            static function ($arguments, $num) {
                return round($num);
            }
        );
        $this->expression->register(
            'substr',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param string $string
             * @param int $start
             * @param int $length
             */
            static function ($arguments, $string, $start, $length) {
                return substr($string, $start, $length);
            }
        );
        $this->expression->register(
            'preg_match',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param string $pattern
             * @param string $subject
             */
            static function ($arguments, $pattern, $subject) {
                return preg_match($pattern, $subject);
            }
        );
        $this->expression->register(
            'ADVISOR_bytime',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param float $num
             * @param int $precision
             */
            static function ($arguments, $num, $precision) {
                return self::byTime($num, $precision);
            }
        );
        $this->expression->register(
            'ADVISOR_timespanFormat',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param string $seconds
             */
            static function ($arguments, $seconds) {
                return Util::timespanFormat((int) $seconds);
            }
        );
        $this->expression->register(
            'ADVISOR_formatByteDown',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param int $value
             * @param int $limes
             * @param int $comma
             */
            static function ($arguments, $value, $limes = 6, $comma = 0) {
                return implode(' ', (array) Util::formatByteDown($value, $limes, $comma));
            }
        );
        $this->expression->register(
            'fired',
            static function (): void {
            },
            /**
             * @param array $arguments
             * @param int $value
             */
            function ($arguments, $value) {
                if (! isset($this->runResult['fired'])) {
                    return 0;
                }

                // Did matching rule fire?
                foreach ($this->runResult['fired'] as $rule) {
                    if ($rule['id'] == $value) {
                        return '1';
                    }
                }

                return '0';
            }
        );
        /* Some global variables for advisor */
        $this->globals = [
            'PMA_MYSQL_INT_VERSION' => $this->dbi->getVersion(),
            'IS_MARIADB' => $this->dbi->isMariaDB(),
        ];
    }

    private function setVariables(): void
    {
        $globalStatus = $this->dbi->fetchResult('SHOW GLOBAL STATUS', 0, 1);
        $globalVariables = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES', 0, 1);

        $sysInfo = SysInfo::get();
        $memory = $sysInfo->memory();
        $systemMemory = ['system_memory' => $memory['MemTotal'] ?? 0];

        $this->variables = array_merge($globalStatus, $globalVariables, $systemMemory);
    }

    /**
     * @param string|int $variable Variable to set
     * @param mixed      $value    Value to set
     */
    public function setVariable($variable, $value): void
    {
        $this->variables[$variable] = $value;
    }

    private function setRules(): void
    {
        $isMariaDB = str_contains($this->variables['version'], 'MariaDB');
        $genericRules = include ROOT_PATH . self::GENERIC_RULES_FILE;

        if (! $isMariaDB && $this->globals['PMA_MYSQL_INT_VERSION'] >= 80003) {
            $this->rules = $genericRules;

            return;
        }

        $extraRules = include ROOT_PATH . self::BEFORE_MYSQL80003_RULES_FILE;
        $this->rules = array_merge($genericRules, $extraRules);
    }

    /**
     * @return array
     */
    public function getRunResult(): array
    {
        return $this->runResult;
    }

    /**
     * @return array
     */
    public function run(): array
    {
        $this->setVariables();
        $this->setRules();
        $this->runRules();

        return $this->runResult;
    }

    /**
     * Stores current error in run results.
     *
     * @param string    $description description of an error.
     * @param Throwable $exception   exception raised
     */
    private function storeError(string $description, Throwable $exception): void
    {
        $this->runResult['errors'][] = $description . ' ' . sprintf(
            __('Error when evaluating: %s'),
            $exception->getMessage()
        );
    }

    /**
     * Executes advisor rules
     */
    private function runRules(): void
    {
        $this->runResult = [
            'fired' => [],
            'notfired' => [],
            'unchecked' => [],
            'errors' => [],
        ];

        foreach ($this->rules as $rule) {
            $this->variables['value'] = 0;
            $precondition = true;

            if (isset($rule['precondition'])) {
                try {
                    $precondition = $this->evaluateRuleExpression($rule['precondition']);
                } catch (Throwable $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed evaluating precondition for rule \'%s\'.'),
                            $rule['name']
                        ),
                        $e
                    );
                    continue;
                }
            }

            if (! $precondition) {
                $this->addRule('unchecked', $rule);

                continue;
            }

            try {
                $value = $this->evaluateRuleExpression($rule['formula']);
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(
                        __('Failed calculating value for rule \'%s\'.'),
                        $rule['name']
                    ),
                    $e
                );
                continue;
            }

            $this->variables['value'] = $value;

            try {
                if ($this->evaluateRuleExpression($rule['test'])) {
                    $this->addRule('fired', $rule);
                } else {
                    $this->addRule('notfired', $rule);
                }
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(
                        __('Failed running test for rule \'%s\'.'),
                        $rule['name']
                    ),
                    $e
                );
            }
        }
    }

    /**
     * Adds a rule to the result list
     *
     * @param string $type type of rule
     * @param array  $rule rule itself
     */
    public function addRule(string $type, array $rule): void
    {
        if ($type !== 'notfired' && $type !== 'fired') {
            $this->runResult[$type][] = $rule;

            return;
        }

        if (isset($rule['justification_formula'])) {
            try {
                $params = $this->evaluateRuleExpression('[' . $rule['justification_formula'] . ']');
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(__('Failed formatting string for rule \'%s\'.'), $rule['name']),
                    $e
                );

                return;
            }

            $rule['justification'] = vsprintf($rule['justification'], $params);
        }

        // Replaces {server_variable} with 'server_variable'
        // linking to /server/variables
        $rule['recommendation'] = preg_replace_callback(
            '/\{([a-z_0-9]+)\}/Ui',
            function (array $matches) {
                return $this->replaceVariable($matches);
            },
            $rule['recommendation']
        );
        $rule['issue'] = preg_replace_callback(
            '/\{([a-z_0-9]+)\}/Ui',
            function (array $matches) {
                return $this->replaceVariable($matches);
            },
            $rule['issue']
        );

        // Replaces external Links with Core::linkURL() generated links
        $rule['recommendation'] = preg_replace_callback(
            '#href=("|\')(https?://[^"\']+)\1#i',
            function (array $matches) {
                return $this->replaceLinkURL($matches);
            },
            $rule['recommendation']
        );

        $this->runResult[$type][] = $rule;
    }

    /**
     * Callback for wrapping links with Core::linkURL
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceLinkURL(array $matches): string
    {
        return 'href="' . Core::linkURL($matches[2]) . '" target="_blank" rel="noopener noreferrer"';
    }

    /**
     * Callback for wrapping variable edit links
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceVariable(array $matches): string
    {
        return '<a href="' . Url::getFromRoute('/server/variables', ['filter' => $matches[1]])
                . '">' . htmlspecialchars($matches[1]) . '</a>';
    }

    /**
     * Runs a code expression, replacing variable names with their respective values
     *
     * @return mixed result of evaluated expression
     */
    private function evaluateRuleExpression(string $expression)
    {
        return $this->expression->evaluate($expression, array_merge($this->variables, $this->globals));
    }

    /**
     * Formats interval like 10 per hour
     *
     * @param float $num       number to format
     * @param int   $precision required precision
     *
     * @return string formatted string
     */
    public static function byTime(float $num, int $precision): string
    {
        if ($num >= 1) { // per second
            $per = __('per second');
        } elseif ($num * 60 >= 1) { // per minute
            $num *= 60;
            $per = __('per minute');
        } elseif ($num * 60 * 60 >= 1) { // per hour
            $num *= 60 * 60;
            $per = __('per hour');
        } else {
            $num *= 24 * 60 * 60;
            $per = __('per day');
        }

        $num = round($num, $precision);

        if ($num == 0) {
            $num = '<' . 10 ** (-$precision);
        }

        return $num . ' ' . $per;
    }
}
