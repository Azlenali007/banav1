<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$error = '';
$subjectVal = (string)($_POST['subject'] ?? '');
$categoryVal = (string)($_POST['category'] ?? 'general');
$priorityVal = (string)($_POST['priority'] ?? 'medium');
$messageVal = (string)($_POST['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $res = TicketModule::createTicket($userId, $subjectVal, $categoryVal, $priorityVal, $messageVal);
    if ($res['success']) {
        Session::setFlash('success', "Support ticket {$res['ticket_uid']} created.");
        Response::redirect('/user/ticket-view.php?id=' . (int)$res['ticket_id']);
    }
    $error = (string)$res['error'];
}

$tickets = Database::fetchAll('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY last_reply_at DESC', [$userId]);

Theme::render('user/tickets', [
    'error' => $error,
    'tickets' => $tickets,
    'subjectVal' => $subjectVal,
    'categoryVal' => $categoryVal,
    'priorityVal' => $priorityVal,
    'messageVal' => $messageVal,
], 'user', [
    'pageTitle' => 'Support Tickets',
    'currentUser' => $currentUser,
]);
