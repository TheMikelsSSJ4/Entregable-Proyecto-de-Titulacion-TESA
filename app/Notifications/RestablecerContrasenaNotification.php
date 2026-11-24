<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo con el enlace de "Olvidé mi contraseña".
 */
class RestablecerContrasenaNotification extends Notification
{
    public function __construct(#[\SensitiveParameter] public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(Usuario $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Usuario $notifiable): MailMessage
    {
        $minutos = (int) config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Restablecer contraseña · '.config('app.name'))
            ->greeting('Hola, '.$notifiable->nombre_completo)
            ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta en el sistema contable.')
            ->action('Restablecer contraseña', route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]))
            ->line("El enlace caduca en {$minutos} minutos.")
            ->line('Si usted no lo solicitó, ignore este correo: su contraseña actual sigue siendo válida.')
            ->salutation('Privileg Distribuciones');
    }
}
