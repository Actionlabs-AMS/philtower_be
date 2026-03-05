<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'Important',
                'slug' => 'important',
                'color' => '#dc3545',
                'active' => true,
            ],
            [
                'name' => 'Featured',
                'slug' => 'featured',
                'color' => '#ffc107',
                'active' => true,
            ],
            [
                'name' => 'New',
                'slug' => 'new',
                'color' => '#28a745',
                'active' => true,
            ],
            [
                'name' => 'Updated',
                'slug' => 'updated',
                'color' => '#17a2b8',
                'active' => true,
            ],
            [
                'name' => 'Draft',
                'slug' => 'draft',
                'color' => '#6c757d',
                'active' => true,
            ],
            [
                'name' => 'Published',
                'slug' => 'published',
                'color' => '#007bff',
                'active' => true,
            ],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}
