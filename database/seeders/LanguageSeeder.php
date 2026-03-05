<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Chinese (Simplified)',
                'code' => 'zh',
                'native_name' => '中文',
                'flag' => '🇨🇳',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Japanese',
                'code' => 'ja',
                'native_name' => '日本語',
                'flag' => '🇯🇵',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Korean',
                'code' => 'ko',
                'native_name' => '한국어',
                'flag' => '🇰🇷',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Tagalog',
                'code' => 'tl',
                'native_name' => 'Tagalog',
                'flag' => '🇵🇭',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        // If setting a default, unset all other defaults first
        $hasDefault = false;
        foreach ($languages as $lang) {
            if ($lang['is_default']) {
                $hasDefault = true;
                break;
            }
        }

        if ($hasDefault) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}

