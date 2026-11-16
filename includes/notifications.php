<?php
declare(strict_types=1);

function user_unread_notifications_count(int $userId): int
{
    return Notification::unreadCount($userId);
}

function user_recent_notifications(int $userId, int $limit = 8): array
{
    return Database::fetchAll(
        'SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ?',
        [$userId, $limit]
    );
}
