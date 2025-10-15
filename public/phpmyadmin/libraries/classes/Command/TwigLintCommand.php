<?php

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use PhpMyAdmin\Template;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Error\Error;
use Twig\Loader\ArrayLoader;
use Twig\Source;

use function array_push;
use function closedir;
use function count;
use function explode;
use function file_get_contents;
use function is_dir;
use function is_file;
use function max;
use function min;
use function opendir;
use function preg_match;
use function readdir;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const E_USER_DEPRECATED;

/**
 * Command that will validate your template syntax and output encountered errors.
 * Author: Marc Weistroff <marc.weistroff@sensiolabs.com>
 * Author: Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * Copyright (c) 2013-2021 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class TwigLintCommand extends Command
{
    /** @var string|null */
    protected static $defaultName = 'lint:twig';

    /** @var string|null */
    protected static $defaultDescription = 'Lint a Twig template and outputs encountered errors';

    protected function configure(): void
    {
        $this
            ->setDescription((string) self::$defaultDescription)
            ->addOption('show-deprecations', null, InputOption::VALUE_NONE, 'Show deprecations as errors');
    }

    /** @return string[] */
    protected function findFiles(string $baseFolder): array
    {
        /* Open the handle */
        $handle = @opendir($baseFolder);
        if ($handle === false) {
            return [];
        }

        $foundFiles = [];

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $itemPath = $baseFolder . DIRECTORY_SEPARATOR . $file;

            if (is_dir($itemPath)) {
                array_push($foundFiles, ...$this->findFiles($itemPath));
                continue;
            }

            if (! is_file($itemPath)) {
                continue;
            }

            $foundFiles[] = $itemPath;
        }

        /* Close the handle */
        closedir($handle);

        return $foundFiles;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $showDeprecations = $input->getOption('show-deprecations');

        if ($showDeprecations) {
            $prevErrorHandler = set_error_handler(
                static function (int $level, string $message, string $file, int $line) use (&$prevErrorHandler) {
                    if ($level === E_USER_DEPRECATED) {
                        $templateLine = 0;
                        if (preg_match('/ at line (\d+)[ .]/', $message, $matches)) {
                            $templateLine = (int) $matches[1];
                        }

                        throw new Error($message, $templateLine);
                    }

                    return $prevErrorHandler ? $prevErrorHandler($level, $message, $file, $line) : false;
                }
            );
        }

        try {
            $filesInfo = $this->getFilesInfo(ROOT_PATH . 'templates');
        } finally {
            if ($showDeprecations) {
                restore_error_handler();
            }
        }

        return $this->display($output, $io, $filesInfo);
    }

    /** @return array{template: string, file: string, valid: bool, line?: int, exception?: Error}[] */
    public function getFilesInfo(string $templatesPath): array
    {
        $filesInfo = [];
        $filesFound = $this->findFiles($templatesPath);
        foreach ($filesFound as $file) {
            $filesInfo[] = $this->validate($this->getTemplateContents($file), $file);
        }

        return $filesInfo;
    }

    /**
     * Allows easier testing
     */
    protected function getTemplateContents(string $filePath): string
    {
        return (string) file_get_contents($filePath);
    }

    /** @return array{template: string, file: string, valid: bool, line?: int, exception?: Error} */
    private function validate(string $template, string $file): array
    {
        $twig = Template::getTwigEnvironment(null);

        $realLoader = $twig->getLoader();
        try {
            $temporaryLoader = new ArrayLoader([$file => $template]);
            $twig->setLoader($temporaryLoader);
            $nodeTree = $twig->parse($twig->tokenize(new Source($template, $file)));
            $twig->compile($nodeTree);
            $twig->setLoader($realLoader);
        } catch (Error $e) {
            $twig->setLoader($realLoader);

            return [
                'template' => $template,
                'file' => $file,
                'line' => $e->getTemplateLine(),
                'valid' => false,
                'exception' => $e,
            ];
        }

        return ['template' => $template, 'file' => $file, 'valid' => true];
    }

    private function display(OutputInterface $output, SymfonyStyle $io, array $filesInfo): int
    {
        $errors = 0;

        foreach ($filesInfo as $info) {
            if ($info['valid'] && $output->isVerbose()) {
                $io->comment('<info>OK</info>' . ($info['file'] ? sprintf(' in %s', $info['file']) : ''));
            } elseif (! $info['valid']) {
                ++$errors;
                $this->renderException($io, $info['template'], $info['exception'], $info['file']);
            }
        }

        if ($errors === 0) {
            $io->success(sprintf('All %d Twig files contain valid syntax.', count($filesInfo)));

            return Command::SUCCESS;
        }

        $io->warning(
            sprintf(
                '%d Twig files have valid syntax and %d contain errors.',
                count($filesInfo) - $errors,
                $errors
            )
        );

        return Command::FAILURE;
    }

    private function renderException(
        SymfonyStyle $output,
        string $template,
        Error $exception,
        ?string $file = null
    ): void {
        $line = $exception->getTemplateLine();

        if ($file) {
            $output->text(sprintf('<error> ERROR </error> in %s (line %s)', $file, $line));
        } else {
            $output->text(sprintf('<error> ERROR </error> (line %s)', $line));
        }

        // If the line is not known (this might happen for deprecations if we fail at detecting the line for instance),
        // we render the message without context, to ensure the message is displayed.
        if ($line <= 0) {
            $output->text(sprintf('<error> >> %s</error> ', $exception->getRawMessage()));

            return;
        }

        foreach ($this->getContext($template, $line) as $lineNumber => $code) {
            $output->text(sprintf(
                '%s %-6s %s',
                $lineNumber === $line ? '<error> >> </error>' : '    ',
                $lineNumber,
                $code
            ));
            if ($lineNumber !== $line) {
                continue;
            }

            $output->text(sprintf('<error> >> %s</error> ', $exception->getRawMessage()));
        }
    }

    private function getContext(string $template, int $line, int $context = 3): array
    {
        $lines = explode("\n", $template);

        $position = max(0, $line - $context);
        $max = min(count($lines), $line - 1 + $context);

        $result = [];
        while ($position < $max) {
            $result[$position + 1] = $lines[$position];
            ++$position;
        }

        return $result;
    }
}
