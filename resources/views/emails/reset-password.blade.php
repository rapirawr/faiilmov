<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Password | faiilmov</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f0f0f; color: #e4e4e7; line-height: 1.6; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #1a1a1a; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; overflow: hidden; }
        .header { padding: 40px 40px 32px; text-align: center; background: linear-gradient(135deg, #1f1f1f 0%, #141414 100%); border-bottom: 1px solid rgba(255,255,255,0.06); }
        .logo-box { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: #e4e2dd; border-radius: 16px; margin-bottom: 16px; }
        .logo-box img { width: 40px; height: 40px; object-fit: contain; }
        .logo-text { font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 6px; }
        .logo-text span { color: #71717a; font-weight: 400; }
        .header-subtitle { font-size: 12px; color: #71717a; text-transform: uppercase; letter-spacing: 0.12em; font-weight: 600; }
        .body { padding: 40px; }
        .greeting { font-size: 14px; color: #a1a1aa; margin-bottom: 16px; }
        .main-text { font-size: 14px; color: #d4d4d8; margin-bottom: 28px; line-height: 1.7; }
        .btn-reset { display: block; text-align: center; background: #ffffff; color: #0f0f0f; font-weight: 700; font-size: 14px; text-decoration: none; padding: 16px 32px; border-radius: 14px; margin-bottom: 28px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 28px 0; }
        .link-fallback { font-size: 12px; color: #71717a; }
        .link-fallback a { color: #a1a1aa; word-break: break-all; }
        .expiry-note { font-size: 12px; color: #52525b; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 8px; margin-bottom: 28px; }
        .expiry-note::before { content: "⏱"; font-size: 14px; }
        .footer { padding: 24px 40px; background: #141414; border-top: 1px solid rgba(255,255,255,0.06); text-align: center; }
        .footer p { font-size: 11px; color: #52525b; line-height: 1.6; }
        .footer a { color: #71717a; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 16px auto;">
                <tr>
                    <td align="center" style="background-color: #e4e2dd; border-radius: 16px; width: 60px; height: 60px; text-align: center; vertical-align: middle; padding: 8px;">
                        <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="faiilmov" width="44" height="44" style="display: block; margin: 0 auto; width: 44px; height: 44px; max-width: 44px; max-height: 44px; border: 0; outline: none; text-decoration: none;">
                    </td>
                </tr>
            </table>
            <div class="logo-text">faiil<span>mov</span></div>
            <div class="header-subtitle">Reset Kata Sandi</div>
        </div>

        <div class="body">
            <p class="greeting">Halo, {{ $notifiable->name }}</p>

            <p class="main-text">
                Kami menerima permintaan untuk mereset kata sandi akun faiilmov yang terhubung dengan email ini.
                Klik tombol di bawah untuk membuat kata sandi baru.
            </p>

            <a href="{{ $resetUrl }}" class="btn-reset">
                Reset Password Sekarang
            </a>

            <div class="expiry-note">
                Link ini akan kadaluarsa dalam <strong>&nbsp;{{ $expiryMinutes }} menit</strong>. Jangan bagikan ke siapapun.
            </div>

            <p class="main-text" style="font-size: 13px;">
                Jika Anda tidak meminta reset password, abaikan email ini | akun Anda tetap aman dan tidak ada perubahan yang dilakukan.
            </p>

            <hr class="divider">

            <div class="link-fallback">
                <p style="margin-bottom: 8px;">Jika tombol di atas tidak berfungsi, salin dan tempel link berikut ke browser Anda:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>
        </div>

        <div class="footer">
            <p>
                Email ini dikirim oleh <a href="{{ config('app.url') }}">faiilmov</a> karena ada permintaan reset password.<br>
                &copy; {{ date('Y') }} faiilmov. All rights reserved.
            </p>
        </div>
    </div>
</div>
</body>
</html>
