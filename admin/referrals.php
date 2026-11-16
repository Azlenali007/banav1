<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('referrals.view');
$canManage = Permission::adminHas($currentAdmin, 'referrals.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    if (!$canManage) {
        Response::abort(403, 'Access Denied', 'You do not have permission (referrals.manage) to modify referrals.');
    }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        set_setting('referral', 'referral_enabled', !empty($_POST['referral_enabled']) ? '1' : '0');
        set_setting('referral', 'referral_reward_type', ($_POST['referral_reward_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed');
        $amount = Validator::parseDecimal($_POST['referral_reward_amount'] ?? '0', 4) ?? '0.0000';
        $percent = Validator::parseDecimal($_POST['referral_reward_percent'] ?? '0', 4) ?? '0.0000';
        $referee = Validator::parseDecimal($_POST['referral_reward_referee'] ?? '0', 4) ?? '0.0000';
        $minDeposit = Validator::parseDecimal($_POST['referral_min_deposit'] ?? '0', 4) ?? '0.0000';
        $event = (string)($_POST['referral_qualifying_event'] ?? 'signup');
        if (!in_array($event, ['signup', 'first_order', 'first_deposit', 'manual'], true)) {
            $event = 'signup';
        }
        set_setting('referral', 'referral_reward_amount', money_format_dec($amount, 4));
        set_setting('referral', 'referral_reward_percent', money_format_dec($percent, 4));
        set_setting('referral', 'referral_reward_referee', money_format_dec($referee, 4));
        set_setting('referral', 'referral_min_deposit', money_format_dec($minDeposit, 4));
        set_setting('referral', 'referral_qualifying_event', $event);
        set_setting('referral', 'referral_auto_approve', !empty($_POST['referral_auto_approve']) ? '1' : '0');
        set_setting('referral', 'referral_terms', trim((string)($_POST['referral_terms'] ?? '')));
        Logger::adminActivity((int)$currentAdmin['id'], 'referral.settings', 'settings', null, 'Updated referral program settings');
        Session::setFlash('success', 'Referral program settings saved.');
    } elseif ($action === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        $amount = trim((string)($_POST['amount'] ?? ''));
        $res = Referral::approve($id, (int)$currentAdmin['id'], $amount !== '' ? $amount : null);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Referral approved and reward credited.' : (string)($res['error'] ?? 'Unable to approve referral.'));
    } elseif ($action === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Referral::reject($id, (int)$currentAdmin['id'], (string)($_POST['note'] ?? ''));
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Referral rejected.' : (string)($res['error'] ?? 'Unable to reject referral.'));
    }

    Response::redirect('/admin/referrals.php');
}

$status = (string)($_GET['status'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if (in_array($status, ['pending', 'qualified', 'rejected'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(ru.username LIKE ? OR ru.email LIKE ? OR re.username LIKE ? OR re.email LIKE ? OR r.code LIKE ?)';
    $like = "%{$q}%";
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM referrals r
     INNER JOIN users ru ON ru.id = r.referrer_id
     INNER JOIN users re ON re.id = r.referred_id
     WHERE {$whereSql}",
    $params
);
$pg = paginate_build($total, $page, 25);

$referrals = Database::fetchAll(
    "SELECT r.*, ru.username AS referrer_username, ru.email AS referrer_email,
            re.username AS referred_username, re.email AS referred_email, re.created_at AS referred_joined,
            (SELECT COUNT(*) FROM referral_rewards rr WHERE rr.referral_id = r.id AND rr.reward_type = 'referrer_bonus' AND rr.status = 'credited') AS referrer_credited
     FROM referrals r
     INNER JOIN users ru ON ru.id = r.referrer_id
     INNER JOIN users re ON re.id = r.referred_id
     WHERE {$whereSql}
     ORDER BY r.id DESC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

$stats = [
    'total' => (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals'),
    'pending' => (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals WHERE status = ?', ['pending']),
    'qualified' => (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals WHERE status = ?', ['qualified']),
    'credited' => (string)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM referral_rewards WHERE status = 'credited'"),
];

Theme::render('admin/referrals', [
    'referrals' => $referrals,
    'stats' => $stats,
    'status' => $status,
    'q' => $q,
    'pg' => $pg,
    'canManage' => $canManage,
], 'admin', [
    'pageTitle' => 'Refer & Earn',
    'currentAdmin' => $currentAdmin,
]);
