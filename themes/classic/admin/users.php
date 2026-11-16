<?php
declare(strict_types=1);
/** @var string $q @var string $statusFilter @var array $users @var array $pg */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6" x-data="{ showCreate: false }">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
    <p class="text-sm text-slate-500">Search accounts, manage statuses, wallets, and API permissions.</p>
  </div>
  <?php if (admin_can('users.manage')): ?>
    <button type="button" @click="showCreate = !showCreate" class="btn-primary px-4 py-2 text-sm">+ Add User</button>

    <div x-show="showCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
      <div @click.away="showCreate = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Create New User</h2>
        <form method="POST" class="space-y-3.5">
          <?= CSRF::field() ?>
          <input type="hidden" name="action" value="create_user">
          <div><label class="smm-label">Full Name</label><input type="text" name="full_name" required class="smm-input"></div>
          <div><label class="smm-label">Username</label><input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,40}" class="smm-input"></div>
          <div><label class="smm-label">Email</label><input type="email" name="email" required class="smm-input"></div>
          <div><label class="smm-label">Password (min 8 chars)</label><input type="password" name="password" required minlength="8" class="smm-input"></div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showCreate = false" class="btn-secondary px-4 py-2 text-sm">Cancel</button>
            <button type="submit" class="btn-primary px-5 py-2 text-sm">Create User</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<form method="GET" class="smm-card p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search username, email, or name..." class="smm-input sm:col-span-2">
  <select name="status" class="smm-input">
    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
  </select>
  <button type="submit" class="btn-primary px-4 py-2 text-sm">Filter</button>
</form>

<div class="smm-card overflow-hidden">
  <?php if (empty($users)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No users found.</div>
  <?php else: ?>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Wallet Balance</th>
            <th>Orders</th>
            <th>Status</th>
            <th>Joined</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td class="font-mono text-xs text-slate-500">#<?= (int)$u['id'] ?></td>
              <td>
                <div class="font-semibold text-slate-900"><?= e($u['username']) ?></div>
                <div class="text-xs text-slate-500"><?= e($u['email']) ?></div>
              </td>
              <td class="font-bold text-violet-700"><?= e(format_money($u['balance'], 2)) ?></td>
              <td><?= (int)$u['order_count'] ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$u['status'])) ?>"><?= e($u['status']) ?></span></td>
              <td class="text-xs text-slate-500"><?= e(format_datetime($u['created_at'])) ?></td>
              <td class="text-right space-x-1.5 whitespace-nowrap">
                <a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$u['id'])) ?>" class="btn-secondary px-2.5 py-1 text-xs">View</a>
                <?php if (admin_can('users.manage')): ?>
                  <a href="<?= e(app_url('/admin/user-edit.php?id=' . (int)$u['id'])) ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                  <form method="POST" class="inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn-secondary px-2.5 py-1 text-xs <?= $u['status'] === 'active' ? 'text-red-700' : 'text-emerald-700' ?>">
                      <?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['q' => $q, 'status' => $statusFilter], 'itemLabel' => 'users']); ?>
  <?php endif; ?>
</div>
