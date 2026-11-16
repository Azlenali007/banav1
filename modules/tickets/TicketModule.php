<?php
declare(strict_types=1);

class TicketModule
{
    public static function createTicket(int $userId, string $subject, string $category, string $priority, string $message): array
    {
        $subject = Validator::cleanString($subject, 200);
        $category = Validator::cleanString($category, 50) ?: 'general';
        $priority = in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium';
        $message = trim($message);

        if (mb_strlen($subject) < 4) {
            return ['success' => false, 'error' => 'Subject must be at least 4 characters.'];
        }
        if (mb_strlen($message) < 10) {
            return ['success' => false, 'error' => 'Message must be at least 10 characters.'];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $uid = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));
            $ticketId = Database::insert(
                'INSERT INTO support_tickets (ticket_uid, user_id, subject, category, priority, status, last_reply_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())',
                [$uid, $userId, $subject, $category, $priority, 'open']
            );
            Database::insert(
                'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())',
                [$ticketId, 'user', $userId, $message]
            );
            $pdo->commit();
            return ['success' => true, 'ticket_id' => $ticketId, 'ticket_uid' => $uid];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('TicketModule::createTicket', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to submit support ticket.'];
        }
    }

    public static function reply(int $ticketId, string $senderType, int $senderId, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['success' => false, 'error' => 'Reply message cannot be empty.'];
        }
        $ticket = Database::fetch('SELECT * FROM support_tickets WHERE id = ? LIMIT 1', [$ticketId]);
        if (!$ticket) {
            return ['success' => false, 'error' => 'Ticket not found.'];
        }
        if ($senderType === 'user' && (int)$ticket['user_id'] !== $senderId) {
            return ['success' => false, 'error' => 'Access denied.'];
        }

        $newStatus = $senderType === 'admin' ? 'answered' : 'open';
        Database::insert(
            'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$ticketId, $senderType, $senderId, $message]
        );
        Database::execute(
            'UPDATE support_tickets SET status = ?, last_reply_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$newStatus, $ticketId]
        );

        if ($senderType === 'admin') {
            Notification::send((int)$ticket['user_id'], 'ticket_reply', 'Support Reply: ' . $ticket['ticket_uid'], "An administrator replied to your ticket: {$ticket['subject']}", '/user/ticket-view.php?id=' . $ticketId, true);
        }

        return ['success' => true];
    }
}
