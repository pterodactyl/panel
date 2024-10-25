<?php

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Routing;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Cache\CacheInterface;

use function file_put_contents;
use function is_file;
use function json_encode;
use function sprintf;
use function str_contains;
use function str_replace;

use const CACHE_DIR;

final class CacheWarmupCommand extends Command
{
    /** @var string|null */
    protected static $defaultName = 'cache:warmup';

    protected function configure(): void
    {
        $this->setDescription('Warms up the Twig templates cache');
        $this->addOption('twig', null, null, 'Warm up twig templates cache.');
        $this->addOption('routing', null, null, 'Warm up routing cache.');
        $this->addOption('twig-po', null, null, 'Warm up twig templates and write file mappings.');
        $this->addOption(
            'env',
            null,
            InputArgument::OPTIONAL,
            'Defines the environment (production or development) for twig warmup',
            'production'
        );
        $this->setHelp('The <info>%command.name%</info> command warms up the cache of the Twig templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $env */
        $env = $input->getOption('env');

        if ($input->getOption('twig') === true && $input->getOption('routing') === true) {
            $output->writeln('Please specify --twig or --routing');

            return Command::FAILURE;
        }

        if ($input->getOption('twig') === true) {
            return $this->warmUpTwigCache($output, $env, false);
        }

        if ($input->getOption('twig-po') === true) {
            return $this->warmUpTwigCache($output, $env, true);
        }

        if ($input->getOption('routing') === true) {
            return $this->warmUpRoutingCache($output);
        }

        $output->writeln('Warming up all caches.', OutputInterface::VERBOSITY_VERBOSE);
        $twigCode = $this->warmUpTwigCache($output, $env, false);
        if ($twigCode !== 0) {
            $output->writeln('Twig cache generation had an error.');

            return $twigCode;
        }

        $routingCode = $this->warmUpRoutingCache($output);
        if ($routingCode !== 0) {
            $output->writeln('Routing cache generation had an error.');

            return $twigCode;
        }

        $output->writeln('Warm up of all caches done.', OutputInterface::VERBOSITY_VERBOSE);

        return Command::SUCCESS;
    }

    private function warmUpRoutingCache(OutputInterface $output): int
    {
        $output->writeln('Warming up the routing cache', OutputInterface::VERBOSITY_VERBOSE);
        Routing::getDispatcher();

        if (is_file(Routing::ROUTES_CACHE_FILE)) {
            $output->writeln('Warm up done.', OutputInterface::VERBOSITY_VERBOSE);

            return Command::SUCCESS;
        }

        $output->writeln(
            sprintf(
                'Warm up did not work, the folder "%s" is probably not writable.',
                CACHE_DIR
            ),
            OutputInterface::VERBOSITY_NORMAL
        );

        return Command::FAILURE;
    }

    private function warmUpTwigCache(
        OutputInterface $output,
        string $environment,
        bool $writeReplacements
    ): int {
        global $cfg, $config, $dbi;

        $output->writeln('Warming up the twig cache', OutputInterface::VERBOSITY_VERBOSE);
        $config = new Config(CONFIG_FILE);
        $cfg['environment'] = $environment;
        $config->set('environment', $cfg['environment']);
        $dbi = new DatabaseInterface(new DbiDummy());
        $tmpDir = ROOT_PATH . 'twig-templates';
        $twig = Template::getTwigEnvironment($tmpDir);

        $output->writeln('Searching for files...', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $templates = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(Template::TEMPLATES_FOLDER),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var CacheInterface $twigCache */
        $twigCache = $twig->getCache(false);
        $replacements = [];
        $output->writeln(
            'Twig debug is: ' . ($twig->isDebug() ? 'enabled' : 'disabled'),
            OutputInterface::VERBOSITY_DEBUG
        );

        $output->writeln('Warming templates', OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($templates as $file) {
            // Skip test files
            if (str_contains($file->getPathname(), '/test/')) {
                continue;
            }

            // force compilation
            if (! $file->isFile() || $file->getExtension() !== 'twig') {
                continue;
            }

            $name = str_replace(Template::TEMPLATES_FOLDER . '/', '', $file->getPathname());
            $output->writeln('Loading: ' . $name, OutputInterface::VERBOSITY_DEBUG);
            /** @psalm-suppress InternalMethod */
            $template = $twig->loadTemplate($twig->getTemplateClass($name), $name);

            if (! $writeReplacements) {
                continue;
            }

            // Generate line map
            /** @psalm-suppress InternalMethod */
            $cacheFilename = $twigCache->generateKey($name, $twig->getTemplateClass($name));
            $template_file = 'templates/' . $name;
            $cache_file = str_replace($tmpDir, 'twig-templates', $cacheFilename);
            /** @psalm-suppress InternalMethod */
            $replacements[$cache_file] = [$template_file, $template->getDebugInfo()];
        }

        if (! $writeReplacements) {
            $output->writeln('Warm up done.', OutputInterface::VERBOSITY_VERBOSE);

            return Command::SUCCESS;
        }

        $output->writeln('Writing replacements...', OutputInterface::VERBOSITY_VERY_VERBOSE);

        // Store replacements in JSON
        if (file_put_contents($tmpDir . '/replace.json', (string) json_encode($replacements)) === false) {
            return Command::FAILURE;
        }

        $output->writeln('Replacements written done.', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('Warm up done.', OutputInterface::VERBOSITY_VERBOSE);

        return Command::SUCCESS;
    }
}
