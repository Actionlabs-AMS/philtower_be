<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .code-container {
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔒 PathCast Security</div>
            <h1>Two-Factor Authentication Code</h1>
        </div>

        <p>Hello {{ $user->user_name ?? 'User' }},</p>

        <p>You have requested a two-factor authentication code for your PathCast account. Use the code below to complete your login:</p>

        <div class="code-container">
            <div style="margin-bottom: 10px; font-weight: bold;">Your verification code is:</div>
            <div class="verification-code">{{ $code }}</div>
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Information:</strong>
            <ul>
                <li>This code will expire in <strong>{{ $expires_in }} minutes</strong></li>
                <li>Never share this code with anyone</li>
                <li>If you didn't request this code, please secure your account immediately</li>
                <li>For security reasons, this code can only be used once</li>
            </ul>
        </div>

        <p>If you're having trouble logging in, you can also use one of your backup codes instead of this email code.</p>

        <p>If you didn't request this code, please:</p>
        <ul>
            <li>Change your password immediately</li>
            <li>Review your account activity</li>
            <li>Contact support if you notice any suspicious activity</li>
        </ul>

        <div class="footer">
            <p>This is an automated security message from PathCast.</p>
            <p>For security reasons, please do not reply to this email.</p>
            <p>© {{ date('Y') }} PathCast. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
