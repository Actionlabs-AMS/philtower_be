<?php

namespace Database\Seeders;

use App\Models\Support\TicketRequest;
use App\Models\Support\ServiceType;
use App\Models\Support\TicketStatus;
use App\Models\Support\Sla;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds ticket_requests. Depends on: UserSeeder, ServiceTypesSeeder, TicketStatusesSeeder, SlasSeeder.
 */
class TicketRequestSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        if (empty($userIds)) {
            $this->command->warn('TicketRequestSeeder: No users found. Run UserSeeder first.');
            return;
        }

        $serviceTypeIds = ServiceType::childTypes()->pluck('id')->toArray();
        if (empty($serviceTypeIds)) {
            $this->command->warn('TicketRequestSeeder: No service types found. Run ServiceTypesSeeder first.');
            return;
        }

        $statusNew = TicketStatus::where('code', 'new')->first();
        $statusAssigned = TicketStatus::where('code', 'assigned')->first();
        $statusInProgress = TicketStatus::where('code', 'in_progress')->first();
        $statusResolved = TicketStatus::where('code', 'resolved')->first();
        $statusClosed = TicketStatus::where('code', 'closed')->first();
        if (!$statusNew || !$statusAssigned) {
            $this->command->warn('TicketRequestSeeder: Ticket statuses not found. Run TicketStatusesSeeder first.');
            return;
        }

        $slaDefault = Sla::where('severity', '3')->first();
        if (!$slaDefault) {
            $this->command->warn('TicketRequestSeeder: SLA not found. Run SlasSeeder first.');
            return;
        }

        $now = now();
        $baseTime = $now->copy()->subDays(14);

        $tickets = [
            [
                'user_id' => $userIds[array_rand($userIds)],
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => 'Network connection drops intermittently in Building A. Need investigation.',
                'attachment_metadata' => null,
                'contact_number' => '+639171234567',
                'contact_name' => 'Juan Dela Cruz',
                'contact_email' => 'juan.delacruz@example.com',
                'ticket_status_id' => $statusNew->id,
                'slas_id' => $slaDefault->id,
                'for_approval' => TicketRequest::FOR_APPROVAL_AUTO,
                'assigned_to' => null,
                'submitted_at' => $baseTime->copy()->addDays(1),
                'resolved_at' => null,
                'closed_at' => null,
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => 'Request for access to HR portal and timekeeping system.',
                'attachment_metadata' => ['files' => [['name' => 'request_form.pdf', 'path' => 'uploads/request_form.pdf']]],
                'contact_number' => '+639281234567',
                'contact_name' => 'Maria Santos',
                'contact_email' => 'maria.santos@example.com',
                'ticket_status_id' => $statusAssigned->id,
                'slas_id' => $slaDefault->id,
                'for_approval' => TicketRequest::FOR_APPROVAL_YES,
                'assigned_to' => $userIds[array_rand($userIds)],
                'submitted_at' => $baseTime->copy()->addDays(2),
                'resolved_at' => null,
                'closed_at' => null,
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => 'Laptop keyboard not working. Some keys unresponsive.',
                'attachment_metadata' => null,
                'contact_number' => null,
                'contact_name' => 'Pedro Reyes',
                'contact_email' => 'pedro.reyes@example.com',
                'ticket_status_id' => $statusInProgress->id,
                'slas_id' => $slaDefault->id,
                'for_approval' => TicketRequest::FOR_APPROVAL_NO,
                'assigned_to' => $userIds[array_rand($userIds)],
                'submitted_at' => $baseTime->copy()->addDays(3),
                'resolved_at' => null,
                'closed_at' => null,
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => 'Printer jam in 3rd floor pantry. Paper stuck in roller.',
                'attachment_metadata' => null,
                'contact_number' => '+639391234567',
                'contact_name' => 'Ana Lopez',
                'contact_email' => 'ana.lopez@example.com',
                'ticket_status_id' => $statusResolved ? $statusResolved->id : $statusClosed->id,
                'slas_id' => $slaDefault->id,
                'for_approval' => TicketRequest::FOR_APPROVAL_AUTO,
                'assigned_to' => $userIds[array_rand($userIds)],
                'submitted_at' => $baseTime->copy()->addDays(5),
                'resolved_at' => $baseTime->copy()->addDays(7),
                'closed_at' => null,
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => 'New employee onboarding - NEO request for next week batch.',
                'attachment_metadata' => null,
                'contact_number' => '+639501234567',
                'contact_name' => 'Carlos Mendoza',
                'contact_email' => 'carlos.mendoza@example.com',
                'ticket_status_id' => $statusClosed ? $statusClosed->id : $statusResolved->id,
                'slas_id' => $slaDefault->id,
                'for_approval' => TicketRequest::FOR_APPROVAL_YES,
                'assigned_to' => $userIds[array_rand($userIds)],
                'submitted_at' => $baseTime->copy()->addDays(8),
                'resolved_at' => $baseTime->copy()->addDays(10),
                'closed_at' => $baseTime->copy()->addDays(11),
            ],
        ];

        foreach ($tickets as $data) {
            TicketRequest::create($data);
        }

        $this->command->info('TicketRequestSeeder: ' . count($tickets) . ' ticket request(s) created.');
    }
}
