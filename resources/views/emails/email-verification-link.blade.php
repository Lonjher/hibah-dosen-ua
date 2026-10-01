{{-- resources/views/emails/email-verification-link.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email</title>
</head>
<body style="font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
             padding: 24px; background: #f4f4f5; margin: 0;">

    <div style="max-width: 480px; margin: 0 auto;">

        <div style="background: #ffffff; padding: 32px; border-radius: 12px;
                    border: 1px solid #e4e4e7;">

            {{-- Logo --}}
            <div style="text-align: center; margin: 0 0 24px;">
                <div style="display: inline-block; width: 48px; height: 48px;
                            background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
                            border-radius: 12px; line-height: 48px; text-align: center;">
                    <span style="font-family: 'Manrope', system-ui, sans-serif;
                                 color: #ffffff; font-size: 22px; font-weight: 700;">
                        {{ strtoupper(substr(config('app.name', 'H'), 0, 1)) }}
                    </span>
                </div>
                <p style="font-family: 'Manrope', system-ui, sans-serif;
                          margin: 12px 0 0; font-size: 13px; font-weight: 600;
                          color: #18181b;">
                    {{ config('app.name', 'Hibah Dosen') }}
                </p>
            </div>

            <div style="height: 1px; background: #f4f4f5; margin: 0 0 24px;"></div>

            <h1 style="font-family: 'Manrope', system-ui, sans-serif;
                       font-size: 18px; margin: 0 0 12px; color: #18181b; font-weight: 600;">
                Verify Your Email
            </h1>

            <p style="color: #52525b; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                Hello <strong style="color: #18181b;">{{ $userName }}</strong>,<br>
                Click the button below to verify your email address and activate your account.
            </p>

            {{-- CTA --}}
            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $verificationUrl }}"
                   style="display: inline-block; padding: 12px 28px;
                          background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
                          color: #ffffff; text-decoration: none; border-radius: 8px;
                          font-size: 14px; font-weight: 600;">
                    Verify Email Address
                </a>
            </div>

            <p style="color: #71717a; font-size: 12px; margin: 0 0 8px;">
                Or copy and paste this link into your browser:
            </p>
            <p style="color: #059669; font-size: 11px; word-break: break-all; margin: 0 0 24px;
                      font-family: ui-monospace, monospace;">
                {{ $verificationUrl }}
            </p>

            <div style="border-top: 1px solid #f4f4f5; padding-top: 16px;">
                <p style="color: #a1a1aa; font-size: 12px; line-height: 1.6; margin: 0;">
                    This link will expire in <strong>60 minutes</strong>.
                    If you did not request this, you can safely ignore this email.
                </p>
            </div>
        </div>

        <p style="text-align: center; color: #a1a1aa; font-size: 11px; margin: 16px 0 0;">
            © {{ date('Y') }} {{ config('app.name', 'Hibah Dosen') }}. All rights reserved.
        </p>
    </div>
</body>
</html>
