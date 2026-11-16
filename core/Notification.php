<?php
declare(strict_types=1);

class Notification
{
    public static function send(?int $userId, string $type, string $title, string $message, ?string $link = null, bool $sendEmail = false): void
    {
        try {
            Database::insert(
                'INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())',
                [$userId, mb_substr($type, 0, 50), mb_substr($title, 0, 200), $message, $link]
            );

            if ($sendEmail && $userId !== null) {
                $user = Database::fetch('SELECT email, full_name FROM users WHERE id = ? LIMIT 1', [$userId]);
                if ($user && !empty($user['email'])) {
                    $siteName = get_setting('site_name', APP_DEFAULT_NAME);
                    $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #E2E8F0;border-radius:12px;">'
                        . '<h2 style="color:#5B21B6;margin:0 0 12px;">' . e($title) . '</h2>'
                        . '<p style="color:#334155;line-height:1.6;">Hello ' . e($user['full_name']) . ',</p>'
                        . '<p style="color:#334155;line-height:1.6;">' . nl2br(e($message)) . '</p>'
                        . '<hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0;">'
                        . '<p style="color:#64748B;font-size:12px;">' . e($siteName) . '</p>'
                        . '</div>';
                    send_mail($user['email'], $title . ' — ' . $siteName, $html);
                }
            }
        } catch (Throwable $e) {
            Logger::error('Notification::send', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int)Database::fetchColumn(
            'SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0',
            [$userId]
        );
    }

    public static function markAllRead(int $userId): void
    {
        Database::execute('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);
    }
}
