<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemsBySubcategory = [
            'Laptop' => ['Configuration', 'Deployment', 'Checking'],
            'Printer' => ['Configuration', 'Deployment', 'Checking'],
            'Peripherals' => ['Deployment', 'Checking'],
            'Projector' => ['Configuration', 'Deployment', 'Checking'],
            'Office 365' => ['Outlook', 'Word', 'Excel', 'Powerpoint'],
            'Adhoc' => ['Nitro', 'Autocad', 'staad', 'Acrobat Reader'],
            'Wired' => ['Patch Cable', 'Harnessing'],
            'Wireless' => ['Corporate', 'Guest'],
            'Setup' => ['Testing'],
            'Creation' => ['AD', 'Email', 'Sharepoint', 'Onedrive', 'Printer', 'VPN', 'NAS'],
            'Update' => ['AD', 'Email', 'Sharepoint'],
            'Deactivation' => ['AD', 'Email', 'Sharepoint'],
            'Checking' => ['Network Folder'],
            'Password Reset' => ['AD', 'Email'],
            'Backup' => ['Sharepoint', 'Onedrive'],
            'Restoration' => ['Network File', 'Local'],
        ];

        foreach ($itemsBySubcategory as $subcategoryName => $itemNames) {
            $subcategory = Category::query()->where('name', $subcategoryName)->first();

            if (! $subcategory) {
                continue;
            }

            foreach ($itemNames as $itemName) {
                $code = $this->slugify($subcategoryName . '-' . $itemName);

                Item::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $itemName,
                        'description' => null,
                        'subcategory_id' => [$subcategory->id],
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

