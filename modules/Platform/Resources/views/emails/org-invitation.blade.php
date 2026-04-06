<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a56db; color: white; padding: 24px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; }
        .token-box { background: #fff; border: 2px dashed #1a56db; padding: 16px; margin: 16px 0; text-align: center; border-radius: 8px; }
        .token { font-family: monospace; font-size: 14px; word-break: break-all; color: #1a56db; font-weight: bold; }
        .btn { display: inline-block; background: #1a56db; color: white; padding: 12px 32px; text-decoration: none; border-radius: 6px; margin-top: 16px; }
        .footer { padding: 16px; text-align: center; font-size: 12px; color: #6b7280; }
        .details { margin: 16px 0; }
        .details dt { font-weight: bold; color: #374151; }
        .details dd { margin: 0 0 8px 0; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 22px;">Organization Invitation</h1>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p><strong>{{ $inviterName }}</strong> has invited you to join <strong>{{ $orgName }}</strong> on the Barick Pharma platform.</p>

        <dl class="details">
            <dt>Organization</dt>
            <dd>{{ $orgName }}</dd>

            <dt>Role</dt>
            <dd>{{ $invitation->role?->name ?? 'Member' }}</dd>

            <dt>Invitation expires</dt>
            <dd>{{ $invitation->expires_at->format('M d, Y \a\t H:i') }}</dd>
        </dl>

        <p>To accept this invitation:</p>
        <ol>
            <li>Open the <strong>Barick Admin</strong> app</li>
            <li>Go to <strong>Organization → Accept Invitation</strong></li>
            <li>Enter the token below</li>
        </ol>

        <div class="token-box">
            <small style="color: #6b7280;">Your invitation token:</small><br>
            <span class="token">{{ $invitation->token }}</span>
        </div>

        <p style="text-align: center; margin-top: 8px;">
            <small style="color: #6b7280;">Or accept directly via the API if you're already logged in:</small>
        </p>

        @php
            $acceptUrl = config('app.url') . '/api/v1/orgs/invitations/' . $invitation->token . '/accept';
        @endphp

        <div style="text-align: center;">
            <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
        </div>

        <p style="margin-top: 20px; font-size: 13px; color: #6b7280;">
            If you did not expect this invitation, you can safely ignore this email.
            The invitation will expire automatically on {{ $invitation->expires_at->format('M d, Y') }}.
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>