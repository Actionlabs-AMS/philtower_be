<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Assigned</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f9f9f9; padding: 24px; border-radius: 8px; }
        .field { margin: 10px 0; }
        .label { font-weight: bold; color: #555; }
        .button { display: inline-block; background-color: #805AD5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 16px; }
        .footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ticket Assigned to You</h2>
        <p>A support ticket has been assigned to you.</p>
        <div class="field"><span class="label">Ticket ID:</span> {{ $ticket->request_number ?? $ticket->id }}</div>
        <div class="field"><span class="label">Description:</span> {{ Str::limit($ticket->description ?? '—', 100) }}</div>
        <div class="field"><span class="label">Status:</span> {{ $ticket->ticketStatus?->label ?? '—' }}</div>
        <div class="field"><span class="label">Contact:</span> {{ $ticket->contact_name ?? $ticket->contact_email ?? '—' }}</div>
        <div class="field"><span class="label">Assigned To:</span> {{ $ticket->assignedTo?->user_login ?? '—' }}</div>
        @if(config('app.frontend_url'))
        <p><a href="{{ rtrim(config('app.frontend_url'), '/') }}/ticket-management/all-tickets/{{ $ticket->id }}" class="button">View Ticket</a></p>
        @endif
        <div class="footer">This is an automated notification from the support system.</div>
    </div>
</body>
</html>
