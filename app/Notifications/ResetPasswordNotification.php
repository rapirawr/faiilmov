<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * How many minutes the token is valid.
     */
    public int $expiryMinutes;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->expiryMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);
    }

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage())
            ->subject('Reset Kata Sandi — faiilmov')
            ->view('emails.reset-password', [
                'resetUrl'      => $resetUrl,
                'expiryMinutes' => $this->expiryMinutes,
                'notifiable'    => $notifiable,
            ]);
    }
}
