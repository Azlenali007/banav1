<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('tickets.view');

$ticketId = (int)($_GET['id'] ?? 0);
$ticket = Database::fetch(
    'SELECT t.*, u.username, u.email FROM support_tickets t INNER JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1',
    [$ticketId]
);
if (!$ticket) {
    Response::abort(404, 'Ticket Not Found', 'The requested ticket does not exist.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('tickets.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? 'reply');

    if ($action === 'close') {
        Database::execute("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?", [$ticketId]);
        Logger::adminActivity((int)$currentAdmin['id'], 'ticket.close', 'ticket', $ticketId, "Closed ticket {$ticket['ticket_uid']}");
        Session::setFlash('success', 'Ticket closed.');
    } elseif ($action === 'reopen') {
        Database::execute("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?", [$ticketId]);
        Logger::adminActivity((int)$currentAdmin['id'], 'ticket.reopen', 'ticket', $ticketId, "Reopened ticket {$ticket['ticket_uid']}");
        Session::setFlash('success', 'Ticket reopened.');
    } else {
        $res = TicketModule::reply($ticketId, 'admin', (int)$currentAdmin['id'], (string)($_POST['message'] ?? ''));
        if ($res['success']) {
            Logger::adminActivity((int)$currentAdmin['id'], 'ticket.reply', 'ticket', $ticketId, "Replied to ticket {$ticket['ticket_uid']}");
            Session::setFlash('success', 'Reply sent to user.');
        } else {
            Session::setFlash('error', (string)$res['error']);
        }
    }
    Response::redirect('/admin/ticket-view.php?id=' . $ticketId);
}

$messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC', [$ticketId]);

Theme::render('admin/ticket-view', [
    'ticketId' => $ticketId,
    'ticket' => $ticket,
    'messages' => $messages,
], 'admin', [
    'pageTitle' => 'Ticket ' . $ticket['ticket_uid'],
    'currentAdmin' => $currentAdmin,
]);
