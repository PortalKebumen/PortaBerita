<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Info Login PortalKebumen</title>
</head>
<body style="font-family: Arial, sans-serif; color: #171B28; line-height: 1.6;">
    <h1 style="font-size: 20px;">{{ $isNewAccount ? 'Akun PortalKebumen Anda Sudah Dibuat' : 'Info Login PortalKebumen' }}</h1>

    <p>Halo {{ $user->name }},</p>

    <p>
        @if ($isNewAccount)
            Administrator telah membuatkan akun untuk Anda di panel redaksi PortalKebumen.
        @else
            Administrator telah mengatur ulang kata sandi akun Anda di panel redaksi PortalKebumen.
        @endif
        Gunakan data berikut untuk masuk:
    </p>

    <table cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 12px 0; font-size: 15px;">
        <tr>
            <td style="padding: 6px 16px 6px 0; color: #5B6378;">Alamat login</td>
            <td style="padding: 6px 0;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></td>
        </tr>
        <tr>
            <td style="padding: 6px 16px 6px 0; color: #5B6378;">Email</td>
            <td style="padding: 6px 0;"><strong>{{ $user->email }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 6px 16px 6px 0; color: #5B6378;">Kata sandi sementara</td>
            <td style="padding: 6px 0;"><strong style="font-family: Consolas, monospace; font-size: 16px; letter-spacing: 1px;">{{ $plainPassword }}</strong></td>
        </tr>
    </table>

    <p>
        <a href="{{ $loginUrl }}" style="display: inline-block; background: #1E398E; color: #ffffff; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 700;">
            Masuk ke PortalKebumen
        </a>
    </p>

    <p><strong>Penting:</strong> setelah masuk, Anda akan diminta mengganti kata sandi sementara ini di halaman Profil. Jangan bagikan email ini maupun alamat login kepada siapa pun.</p>

    <p style="color: #5B6378; font-size: 13px;">Jika Anda tidak merasa memiliki akun di PortalKebumen, abaikan email ini dan beri tahu redaksi.</p>
</body>
</html>
