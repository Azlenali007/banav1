<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$ticketId = (int)($_GET['id'] ?? 0);

$ticket = Database::fetch('SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1', [$ticketId, $userId]);
if (!$ticket) {
    Response::abort(404, 'Ticket Not Found', 'This support ticket does not exist or does not belong to your account.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? 'reply');
    if ($action === 'close') {
        Database::execute("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ? AND user_id = ?", [$ticketId, $userId]);
        Session::setFlash('success', 'Ticket closed.');
    } else {
        if ($ticket['status'] === 'closed') {
            Session::setFlash('error', 'Closed tickets cannot receive replies.');
        } else {
            $res = TicketModule::reply($ticketId, 'user', $userId, (string)($_POST['message'] ?? ''));
            Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Reply posted.' : (string)$res['error']);
        }
    }
    Response::redirect('/user/ticket-view.php?id=' . $ticketId);
}

$messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC', [$ticketId]);

Theme::render('user/ticket-view', [
    'ticket' => $ticket,
    'messages' => $messages,
], 'user', [
    'pageTitle' => 'Ticket ' . $ticket['ticket_uid'],
    'currentUser' => $currentUser,
]);
