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
        $tree = [
            'Hardware' => ['Laptop', 'Printer', 'Peripherals', 'Projector'],
            'Software' => ['Office 365', 'Adhoc'],
            'Network' => ['Wired', 'Wireless', 'Setup'],
            'Access' => ['Creation', 'Update', 'Deactivation', 'Checking', 'Password Reset'],
            'Data' => ['Backup', 'Restoration'],
        ];

        foreach ($tree as $parentName => $children) {
            $parentCode = $this->slugify($parentName);

            $parent = Category::updateOrCreate(
                ['code' => $parentCode],
                [
                    'name' => $parentName,
                    'descriptions' => null,
                    'parent_id' => null,
                    'active' => true,
                ]
            );

            foreach ($children as $childName) {
                $childCode = $this->slugify($parentName . '-' . $childName);

                Category::updateOrCreate(
                    ['code' => $childCode],
                    [
                        'name' => $childName,
                        'descriptions' => null,
                        'parent_id' => $parent->id,
                        'active' => true,
                    ]
                );
            }
        }
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
