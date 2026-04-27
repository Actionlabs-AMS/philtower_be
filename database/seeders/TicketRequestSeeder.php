<?php

namespace Database\Seeders;

use App\Models\Support\TicketRequest;
use App\Models\Support\ServiceType;
use App\Models\Support\TicketStatus;
use App\Models\Support\Sla;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Item;
use Illuminate\Database\Seeder;

class TicketRequestSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();

        $categories = Category::with('children')->get();
        $subcategories = Subcategory::all();
        $items = Item::all();

        if ($categories->isEmpty() || $subcategories->isEmpty() || $items->isEmpty()) {
            $this->command->warn('Missing category/subcategory/item data. Run their seeders first.');
            return;
        }

        $serviceTypeIds = ServiceType::childTypes()->pluck('id')->toArray();

        $statusNew = TicketStatus::where('code', 'new')->first();
        $statusAssigned = TicketStatus::where('code', 'assigned')->first();
        $statusInProgress = TicketStatus::where('code', 'in_progress')->first();
        $statusResolved = TicketStatus::where('code', 'resolved')->first();
        $statusClosed = TicketStatus::where('code', 'closed')->first();
        $statusPending = TicketStatus::where('code', 'pending')->first();
        $statusForApproval = TicketStatus::where('code', 'for_approval')->first();
        $statusCancelled = TicketStatus::where('code', 'cancelled')->first();

        $slas = Sla::all()->keyBy('severity');
        $slaDefault = $slas->get('3') ?? Sla::first();

        $now = now();
        $baseTime = $now->copy()->subDays(30);

        $tickets = [];
        $day = 0;

        foreach (range(0, 19) as $i) {

            $contactName = fake()->name();
            $contactEmail = fake()->safeEmail();
            $contactPhone = '+639' . rand(100000000, 999999999);

            // 🎯 RANDOM BUT VALID HIERARCHY
            $category = $categories->random();

            $subcategory = $subcategories
                ->where('category_id', $category->id)
                ->random();

            $item = $items
                ->where('subcategory_id', $subcategory->id)
                ->random();

            $userId = $userIds[array_rand($userIds)];
            $assignedId = in_array($i, [0, 1, 7, 12]) ? null : $userIds[array_rand($userIds)];

            $day += rand(1, 2);
            $submittedAt = $baseTime->copy()->addDays($day);

            $sla = $slas->get((string) (($i % 5) + 1)) ?? $slaDefault;

            $statusRow = match ($i % 12) {
                0, 1 => $statusNew,
                2, 3 => $statusAssigned,
                4, 5 => $statusInProgress,
                6 => $statusPending,
                7 => $statusForApproval ?? $statusAssigned,
                8 => $statusResolved,
                9, 10 => $statusClosed,
                11 => $statusCancelled ?? $statusClosed,
                default => $statusInProgress,
            };

            $resolvedAt = null;
            $closedAt = null;

            if ($statusRow->is_closed ?? false) {
                $resolvedAt = $submittedAt->copy()->addDays(rand(2, 5));
                $closedAt = $resolvedAt->copy()->addDays(rand(0, 2));
            } elseif ($statusRow->code === 'resolved') {
                $resolvedAt = $submittedAt->copy()->addDays(rand(2, 6));
            }

            $tickets[] = [
                'user_id' => $userId,

                // ✅ NEW RELATIONSHIP FIELDS
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'item_id' => $item->id,

                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => fake()->sentence(12),

                'attachment_metadata' => $i % 4 === 0
                    ? [['name' => 'attachment.pdf', 'file_url' => null]]
                    : null,

                'contact_number' => $contactPhone,
                'contact_name' => $contactName,
                'contact_email' => $contactEmail,

                'ticket_status_id' => $statusRow->id,
                'slas_id' => $sla->id,
                'ticket_priority_id' => ($i % 4) + 1,

                'for_approval' => [
                    TicketRequest::FOR_APPROVAL_AUTO,
                    TicketRequest::FOR_APPROVAL_YES,
                    TicketRequest::FOR_APPROVAL_NO
                ][$i % 3],

                'assigned_to' => $assignedId,

                'submitted_at' => $submittedAt,
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
            ];
        }

        foreach ($tickets as $data) {
            TicketRequest::create($data);
        }

        $this->command->info('TicketRequestSeeder: ' . count($tickets) . ' tickets created with category hierarchy.');
    }
}