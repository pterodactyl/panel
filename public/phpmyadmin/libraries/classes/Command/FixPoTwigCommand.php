<?php

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_get_contents;
use function file_put_contents;
use function intval;
use function is_array;
use function json_decode;
use function preg_replace_callback;

use const ROOT_PATH;

final class FixPoTwigCommand extends Command
{
    /** @var string|null */
    protected static $defaultName = 'fix-po-twig';

    private const POT_FILE = ROOT_PATH . 'po/phpmyadmin.pot';
    private const REPLACE_FILE = ROOT_PATH . 'twig-templates/replace.json';

    protected function configure(): void
    {
        $this->setDescription('Fixes POT file for Twig templates');
        $this->setHelp(
            'The <info>%command.name%</info> command fixes the Twig file name and line number in the'
            . ' POT file to match the Twig template and not the compiled Twig file.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $replaceFile = file_get_contents(self::REPLACE_FILE);
        if ($replaceFile === false) {
            return Command::FAILURE;
        }

        $replacements = json_decode($replaceFile, true);
        if (! is_array($replacements)) {
            return Command::FAILURE;
        }

        /* Read pot file */
        $pot = file_get_contents(self::POT_FILE);
        if ($pot === false) {
            return Command::FAILURE;
        }

        /* Do the replacements */
        $pot = preg_replace_callback(
            '@(twig-templates[0-9a-f/]*.php):([0-9]*)@',
            static function (array $matches) use ($replacements): string {
                $filename = $matches[1];
                $line = intval($matches[2]);
                $replace = $replacements[$filename];
                foreach ($replace[1] as $cacheLine => $result) {
                    if ($line >= $cacheLine) {
                        return $replace[0] . ':' . $result;
                    }
                }

                return $replace[0] . ':0';
            },
            $pot
        );
        if ($pot === null) {
            return Command::FAILURE;
        }

        if (file_put_contents(self::POT_FILE, $pot) === false) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
