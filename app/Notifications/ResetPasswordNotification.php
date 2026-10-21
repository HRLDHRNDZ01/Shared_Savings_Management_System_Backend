<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expire = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset password', $this->resetUrl($notifiable->getEmailForPasswordReset()))
            ->line("This password reset link expires in {$expire} minutes.")
            ->line('If you did not request a password reset, no further action is required.');
    }

    public function resetUrl(string $email): string
    {
        $frontend = collect(explode(',', (string) config('app.frontend_url')))
            ->map(fn (string $url) => trim($url))
            ->first(fn (string $url) => $url !== '' && $url !== '*');

        $base = rtrim($frontend ?: (string) config('app.url'), '/');

        return $base.'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);
    }
}
