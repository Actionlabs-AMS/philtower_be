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
        $statusPending = TicketStatus::where('code', 'pending')->first();
        $statusForApproval = TicketStatus::where('code', 'for_approval')->first();
        $statusCancelled = TicketStatus::where('code', 'cancelled')->first();
        if (!$statusNew || !$statusAssigned) {
            $this->command->warn('TicketRequestSeeder: Ticket statuses not found. Run TicketStatusesSeeder first.');
            return;
        }

        $slas = Sla::all()->keyBy('severity');
        $slaDefault = $slas->get('3') ?? Sla::first();
        if (!$slaDefault) {
            $this->command->warn('TicketRequestSeeder: SLA not found. Run SlasSeeder first.');
            return;
        }

        $now = now();
        $baseTime = $now->copy()->subDays(30);

        $contacts = [
            ['name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com', 'phone' => '+639171234567'],
            ['name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'phone' => '+639281234567'],
            ['name' => 'Pedro Reyes', 'email' => 'pedro.reyes@example.com', 'phone' => null],
            ['name' => 'Ana Lopez', 'email' => 'ana.lopez@example.com', 'phone' => '+639391234567'],
            ['name' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@example.com', 'phone' => '+639501234567'],
            ['name' => 'Elena Torres', 'email' => 'elena.torres@example.com', 'phone' => '+639612345678'],
            ['name' => 'Roberto Silva', 'email' => 'roberto.silva@example.com', 'phone' => '+639723456789'],
            ['name' => 'Liza Fernandez', 'email' => 'liza.fernandez@example.com', 'phone' => '+639834567890'],
            ['name' => 'Miguel Ocampo', 'email' => 'miguel.ocampo@example.com', 'phone' => '+639945678901'],
            ['name' => 'Sofia Ramos', 'email' => 'sofia.ramos@example.com', 'phone' => '+639156789012'],
        ];

        $descriptions = [
            'Network connection drops intermittently in Building A. Need investigation.',
            'Request for access to HR portal and timekeeping system.',
            'Laptop keyboard not working. Some keys unresponsive.',
            'Printer jam in 3rd floor pantry. Paper stuck in roller.',
            'New employee onboarding - NEO request for next week batch.',
            'Email client cannot connect to exchange server after password change.',
            'Monitor flickering on second floor workstation 12. Started this morning.',
            'Request for software installation: Adobe Creative Suite and VS Code.',
            'VPN disconnects every 30 minutes. Need stable connection for WFH.',
            'Shared drive access denied for new project folder. User in Finance dept.',
            'Phone extension not receiving external calls. Line 3401.',
            'Meeting room projector not turning on. Room 5B.',
            'Password reset and MFA setup for contractor account.',
            'Laptop hard drive full. Need cleanup or replacement.',
            'Request to restore deleted files from backup (last Tuesday).',
            'Application error when submitting timesheet. Screenshot attached.',
            'WiFi slow in east wing. Multiple users reporting.',
            'Duplicate invoice request - need reprint for accounting.',
            'Access card not working for main entrance. Already replaced battery.',
            'Request to extend VPN account for 3 months.',
        ];

        $tickets = [];
        $day = 0;
        foreach ($descriptions as $i => $description) {
            $contact = $contacts[$i % count($contacts)];
            $userId = $userIds[array_rand($userIds)];
            $assignedId = in_array($i, [0, 1, 7, 12]) ? null : $userIds[array_rand($userIds)];
            $day += (int) (rand(0, 2)) + 1;
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
            if (!$statusRow) {
                $statusRow = $statusInProgress;
            }

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
                'parent_ticket_id' => null,
                'service_type_id' => $serviceTypeIds[array_rand($serviceTypeIds)],
                'description' => $description,
                'attachment_metadata' => $i % 4 === 0 ? [['name' => 'attachment.pdf', 'file_url' => null]] : null,
                'contact_number' => $contact['phone'],
                'contact_name' => $contact['name'],
                'contact_email' => $contact['email'],
                'ticket_status_id' => $statusRow->id,
                'slas_id' => $sla->id,
                'for_approval' => [TicketRequest::FOR_APPROVAL_AUTO, TicketRequest::FOR_APPROVAL_YES, TicketRequest::FOR_APPROVAL_NO][$i % 3],
                'assigned_to' => $assignedId,
                'submitted_at' => $submittedAt,
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
            ];
        }

        foreach ($tickets as $data) {
            TicketRequest::create($data);
        }

        $this->command->info('TicketRequestSeeder: ' . count($tickets) . ' ticket request(s) created.');
    }
}
