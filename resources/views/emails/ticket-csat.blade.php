<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f9f9f9; padding: 24px; border-radius: 8px; }
        .field { margin: 10px 0; }
        .label { font-weight: bold; color: #555; }
        .stars { margin: 20px 0; }
        .star-btn { display: inline-block; background-color: #ECC94B; color: #333; padding: 10px 18px; text-decoration: none; border-radius: 5px; margin: 4px; font-size: 18px; font-weight: bold; }
        .footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>How did we do?</h2>
        <p>Your support ticket has been resolved. We'd love to hear your feedback!</p>
        <div class="field"><span class="label">Ticket ID:</span> {{ $ticket->request_number ?? $ticket->id }}</div>
        <div class="field"><span class="label">Service:</span> {{ $ticket->serviceType?->name ?? '—' }}</div>
        <div class="field"><span class="label">Resolved by:</span> {{ $ticket->assignedTo?->user_login ?? '—' }}</div>
        @if(config('app.frontend_url') && $ticket->csat_token)
        <p>Please click a star to rate your experience:</p>
        <div class="stars">
            @for($i = 1; $i <= 5; $i++)
            <a href="{{ rtrim(config('app.frontend_url'), '/') }}/csat/{{ $ticket->csat_token }}?rating={{ $i }}" class="star-btn">{{ $i }} ★</a>
            @endfor
        </div>
        @endif
        <div class="footer">This is an automated notification from the support system. You only need to rate once.</div>
    </div>
</body>
</html>
