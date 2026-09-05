<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Reset Kata Sandi PortalKebumen</title>
</head>
<body style="font-family: Arial, sans-serif; color: #171B28; line-height: 1.6;">
    <h1 style="font-size: 20px;">Reset Kata Sandi PortalKebumen</h1>

    <p>Halo {{ $user->name }},</p>

    <p>Kami menerima permintaan reset kata sandi untuk akun PortalKebumen kamu. Klik tautan berikut untuk membuat kata sandi baru:</p>

    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; background: #1E398E; color: #ffffff; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 700;">
            Reset kata sandi
        </a>
    </p>

    <p>Jika tombol tidak dapat dibuka, salin tautan ini ke browser:</p>
    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

    <p>Abaikan email ini jika kamu tidak meminta reset kata sandi.</p>
</body>
</html>
