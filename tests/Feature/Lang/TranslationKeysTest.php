<?php

declare(strict_types=1);

namespace Tests\Feature\Lang;

use Tests\TestCase;

class TranslationKeysTest extends TestCase
{
    public function test_it_has_all_translation_keys_in_all_supported_languages(): void
    {
        $languages = ['pt', 'en', 'es'];
        $translations = [];

        foreach ($languages as $lang) {
            $langFile = lang_path("{$lang}.json");
            $this->assertFileExists($langFile, "Language file {$lang}.json must exist");

            $translations[$lang] = json_decode(file_get_contents($langFile), true);
        }

        $allKeys = [];
        foreach ($translations as $langTranslations) {
            $allKeys = array_merge($allKeys, array_keys($langTranslations));
        }
        $allKeys = array_unique($allKeys);

        foreach ($languages as $lang) {
            foreach ($allKeys as $key) {
                $this->assertArrayHasKey($key, $translations[$lang], "Missing translation key '{$key}' in {$lang}.json");
            }
        }
    }

    public function test_it_has_valid_json_in_all_language_files(): void
    {
        $languages = ['pt', 'en', 'es'];

        foreach ($languages as $lang) {
            $langFile = lang_path("{$lang}.json");
            $content = file_get_contents($langFile);

            $decoded = json_decode($content, true);

            $this->assertSame(JSON_ERROR_NONE, json_last_error(), "Invalid JSON in {$lang}.json");
            $this->assertIsArray($decoded, "{$lang}.json must contain an array");
        }
    }
}
