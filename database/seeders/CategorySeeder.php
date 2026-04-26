<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'General',
                'code' => 'general',
                'descriptions' => 'General category for miscellaneous content',
                'parent_id' => null,
                'active' => true,
            ],
            [
                'name' => 'Technology',
                'code' => 'technology',
                'descriptions' => 'Technology related content',
                'parent_id' => null,
                'active' => true,
            ],
            [
                'name' => 'Business',
                'code' => 'business',
                'descriptions' => 'Business related content',
                'parent_id' => null,
                'active' => true,
            ],
            [
                'name' => 'Education',
                'code' => 'education',
                'descriptions' => 'Education related content',
                'parent_id' => null,
                'active' => true,
            ],
            // Sub-categories
            [
                'name' => 'Web Development',
                'code' => 'web-development',
                'descriptions' => 'Web development subcategory',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
            ],
            [
                'name' => 'Mobile Development',
                'code' => 'mobile-development',
                'descriptions' => 'Mobile development subcategory',
                'parent_id' => null, // Will be set after parent is created
                'active' => true,
            ],
        ];

        // Create parent categories first
        $parentCategories = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] === null && in_array($category['code'], ['general', 'technology', 'business', 'education'])) {
                $cat = Category::create($category);
                $parentCategories[$category['code']] = $cat->id;
            }
        }

        // Create sub-categories
        foreach ($categories as $category) {
            if ($category['parent_id'] === null && !in_array($category['code'], ['general', 'technology', 'business', 'education'])) {
                $parentId = null;
                
                // Determine parent based on code
                if (in_array($category['code'], ['web-development', 'mobile-development'])) {
                    $parentId = $parentCategories['technology'] ?? null;
                }
                
                $category['parent_id'] = $parentId;
                Category::create($category);
            }
        }
    }
}
