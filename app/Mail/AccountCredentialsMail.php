<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Info login (email + kata sandi sementara + alamat login rahasia) yang dikirim admin ke pengguna.
 * Kata sandi ini wajib diganti saat pertama masuk (users.must_change_password).
 */
class AccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        #[\SensitiveParameter]
        public string $plainPassword,
        public bool $isNewAccount = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isNewAccount ? 'Akun PortalKebumen Anda Sudah Dibuat' : 'Info Login PortalKebumen',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.account-credentials',
            with: [
                'loginUrl' => route('login'),
            ],
        );
    }
}
