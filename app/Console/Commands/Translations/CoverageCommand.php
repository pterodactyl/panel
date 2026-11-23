<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Translations;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Translations\LanguageRegistry;
use Pterodactyl\Services\Translations\TranslationCoverage;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[Description('Compare a locale with the English translations: missing and stale keys, placeholder mistakes and conflicts between language packs.')]
#[Signature('p:lang:coverage
                            {locale : The locale to check, such as de or pt_BR.}
                            {--extension= : Check only this language pack, on its own.}
                            {--json : Print the report as JSON.}')]
class CoverageCommand extends Command
{
    public function handle(LanguageRegistry $languages, TranslationCoverage $coverage): int
    {
        $locale = (string) $this->argument('locale');
        if (preg_match(ExtensionManifest::LOCALE_REGEX, $locale) !== 1) {
            $this->components->error("\"{$locale}\" is not a locale such as \"de\" or \"pt_BR\".");

            return self::INVALID;
        }

        $pack = null;
        $identifier = $this->option('extension');
        if (is_string($identifier)) {
            $pack = $languages->packs()->get($identifier);
            if (! $pack instanceof ExtensionManifest || ! in_array($locale, $pack->languages, true)) {
                $this->components->error("No enabled language pack \"{$identifier}\" provides \"{$locale}\".");

                return self::FAILURE;
            }
        } elseif (! $languages->isAvailable($locale)) {
            $this->components->error("No enabled language pack provides \"{$locale}\".");

            return self::FAILURE;
        }

        $report = $coverage->report($locale, $pack);
        $failed = $report['missing'] !== [] || $report['invalid'] !== [] || $report['unknown_placeholders'] !== [];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $percentage = $report['total'] === 0 ? 100 : round($report['translated'] / $report['total'] * 100, 1);
        $this->components->info(sprintf('%s%s: %d of %d keys translated (%s%%).', $locale, $pack instanceof ExtensionManifest ? " ({$pack->id})" : '', $report['translated'], $report['total'], $percentage));

        $this->section('Missing', $report['missing']);
        $this->section('Not strings', $report['invalid']);
        $this->section('Placeholders English does not have', $this->describe($report['unknown_placeholders']));
        $this->section('Placeholders left out', $this->describe($report['dropped_placeholders']));
        $this->section('Not in English', $report['stale']);
        $this->section('Conflicts between packs', array_map(
            fn (array $conflict): string => sprintf('%s: %s, %s wins', $conflict['key'], implode(', ', $conflict['providers']), $conflict['winner']),
            $report['conflicts'],
        ));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, list<string>>  $placeholders
     * @return list<string>
     */
    private function describe(array $placeholders): array
    {
        $lines = [];
        foreach ($placeholders as $key => $names) {
            $json = str_starts_with($key, LanguageRegistry::JSON_PREFIX);
            $lines[] = $key.': '.implode(', ', array_map(fn (string $name): string => $json ? '{{'.$name.'}}' : ':'.$name, $names));
        }

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     */
    private function section(string $title, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        $this->newLine();
        $this->components->twoColumnDetail("<fg=yellow>{$title}</>", (string) count($lines));
        // bulletList() strips trailing punctuation, which is part of a JSON key.
        foreach ($lines as $line) {
            $this->line('  <fg=gray>⇂</> '.OutputFormatter::escape($line));
        }
    }
}
