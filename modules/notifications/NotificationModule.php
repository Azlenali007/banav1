<?php
declare(strict_types=1);

class NotificationModule
{
    public static function broadcast(string $title, string $message, string $type = 'announcement'): void
    {
        Notification::send(null, $type, $title, $message, '/user/notifications.php', false);
    }
}
