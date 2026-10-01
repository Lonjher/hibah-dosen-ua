<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; padding: 24px; background: #f4f4f5; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; padding: 32px; border-radius: 12px;">
        <h1 style="font-size: 18px; margin: 0 0 12px; color: #18181b;">
            Verifikasi Email
        </h1>

        <p style="color: #52525b; font-size: 14px; line-height: 1.6; margin: 0 0 20px;">
            Halo <strong>{{ $userName }}</strong>,<br>
            Gunakan kode berikut untuk memverifikasi email Anda:
        </p>

        <div style="font-size: 32px; font-weight: 700; letter-spacing: 8px; text-align: center; padding: 16px 12px; background: #f4f4f5; border-radius: 8px; color: #18181b; font-family: ui-monospace, monospace;">
            {{ $code }}
        </div>

        <p style="color: #a1a1aa; font-size: 12px; line-height: 1.6; margin: 20px 0 0;">
            Kode ini berlaku selama <strong>15 menit</strong>. Jika Anda tidak meminta kode ini, abaikan email ini.
        </p>
    </div>
</body>
</html>
