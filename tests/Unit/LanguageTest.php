<?php

namespace Tests\Unit;

use Tests\TestCase;
use Pterodactyl\Models\User;

class LanguageTest extends TestCase
{
    public function test_ja_is_in_available_languages()
    {
        $languages = (new User())->getAvailableLanguages();

        $this->assertArrayHasKey('ja', $languages, "The 'ja' language code was not found in available languages.");
    }

    public function test_ja_translation_files_contain_japanese_chars()
    {
        $files = glob(resource_path('lang/ja/*.php')) ?: [];

        $this->assertNotEmpty($files, "No translation files found in resources/lang/ja.");

        foreach ($files as $file) {
            $translations = require $file;
            $json = json_encode($translations, JSON_UNESCAPED_UNICODE);

            $this->assertMatchesRegularExpression('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $json, "File {$file} does not appear to contain Japanese characters.");
        }
    }
}
