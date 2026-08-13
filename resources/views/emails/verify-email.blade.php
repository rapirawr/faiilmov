<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verifikasi Email — faiilmov</title>
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
        .btn-verify { display: block; text-align: center; background: #ffffff; color: #0f0f0f; font-weight: 700; font-size: 14px; text-decoration: none; padding: 16px 32px; border-radius: 14px; margin-bottom: 28px; }
        .benefit-list { margin: 0 0 28px; padding: 0; list-style: none; }
        .benefit-list li { font-size: 13px; color: #a1a1aa; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 10px; }
        .benefit-list li:last-child { border-bottom: none; }
        .benefit-list li::before { content: "✓"; color: #22c55e; font-weight: 700; font-size: 12px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 28px 0; }
        .link-fallback { font-size: 12px; color: #71717a; }
        .link-fallback a { color: #a1a1aa; word-break: break-all; }
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
            <div class="header-subtitle">Verifikasi Email</div>
        </div>

        <div class="body">
            <p class="greeting">Halo, {{ $notifiable->name }} 👋</p>

            <p class="main-text">
                Terima kasih telah bergabung dengan faiilmov! Untuk mengaktifkan akun Anda sepenuhnya,
                verifikasi alamat email ini dengan klik tombol di bawah.
            </p>

            <ul class="benefit-list">
                <li>Tulis ulasan & beri rating film</li>
                <li>Buat & ikuti sesi Nonton Bareng</li>
                <li>Sinkronisasi watchlist ke aplikasi mobile</li>
            </ul>

            <a href="{{ $verificationUrl }}" class="btn-verify">
                ✉️ &nbsp; Verifikasi Email Sekarang
            </a>

            <p class="main-text" style="font-size: 13px;">
                Link ini hanya berlaku selama <strong>60 menit</strong>. Jika sudah kadaluarsa,
                login dan klik "Kirim Ulang" di halaman verifikasi.
            </p>

            <hr class="divider">

            <div class="link-fallback">
                <p style="margin-bottom: 8px;">Jika tombol di atas tidak berfungsi, salin dan tempel link berikut ke browser Anda:</p>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
            </div>
        </div>

        <div class="footer">
            <p>
                Jika Anda tidak mendaftar di <a href="{{ config('app.url') }}">faiilmov</a>, abaikan email ini.<br>
                &copy; {{ date('Y') }} faiilmov. All rights reserved.
            </p>
        </div>
    </div>
</div>
</body>
</html>
