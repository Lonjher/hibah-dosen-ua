<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password</title>
</head>
<body style="font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
             padding: 24px; background: #f4f4f5; margin: 0; -webkit-font-smoothing: antialiased;">

    <div style="max-width: 480px; margin: 0 auto;">

        {{-- Card --}}
        <div style="background: #ffffff; padding: 32px; border-radius: 12px;
                    border: 1px solid #e4e4e7;">

            {{-- Logo / Brand --}}
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
                          color: #18181b; letter-spacing: -0.01em;">
                    {{ config('app.name', 'Hibah Dosen') }}
                </p>
            </div>

            {{-- Divider --}}
            <div style="height: 1px; background: #f4f4f5; margin: 0 0 24px;"></div>

            {{-- Heading --}}
            <h1 style="font-family: 'Manrope', system-ui, sans-serif;
                       font-size: 18px; line-height: 1.3; margin: 0 0 12px;
                       color: #18181b; font-weight: 600; letter-spacing: -0.01em;">
                Reset Your Password
            </h1>

            {{-- Body --}}
            <p style="color: #52525b; font-size: 14px; line-height: 1.6; margin: 0 0 20px;">
                Hello <strong style="color: #18181b;">{{ $userName }}</strong>,<br>
                We received a request to reset the password for your account. Click the button below to continue.
            </p>

            {{-- CTA Button --}}
            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $resetUrl }}"
                   style="display: inline-block; padding: 12px 28px;
                          background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
                          color: #ffffff; text-decoration: none; border-radius: 8px;
                          font-size: 14px; font-weight: 600; letter-spacing: 0.01em;">
                    Reset Password
                </a>
            </div>

            {{-- Fallback Link --}}
            <p style="color: #71717a; font-size: 12px; line-height: 1.6; margin: 0 0 8px;">
                Or copy and paste the following link into your browser:
            </p>
            <p style="color: #059669; font-size: 11px; line-height: 1.5;
                      word-break: break-all; margin: 0 0 24px;
                      font-family: ui-monospace, 'SF Mono', Menlo, monospace;">
                {{ $resetUrl }}
            </p>

            {{-- Notice --}}
            <div style="border-top: 1px solid #f4f4f5; padding-top: 16px;">
                <p style="color: #a1a1aa; font-size: 12px; line-height: 1.6; margin: 0;">
                    This link will expire in <strong style="color: #71717a;">60 minutes</strong>.
                    If you did not request a password reset, you can safely ignore this email — your account remains secure.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <p style="text-align: center; color: #a1a1aa; font-size: 11px;
                  line-height: 1.6; margin: 16px 0 0;">
            © {{ date('Y') }} {{ config('app.name', 'Hibah Dosen') }}. All rights reserved.
        </p>
        <p style="text-align: center; color: #d4d4d8; font-size: 10px; margin: 4px 0 0;">
            This is an automated message, please do not reply.
        </p>
    </div>
</body>
</html>
