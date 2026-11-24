<?php
require __DIR__ . '/../config.php';
admin_required();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'settings') {
        $upi = trim((string)($_POST['upi_id'] ?? ''));
        if (!preg_match('/^[\w.\-]+@[\w.\-]+$/', $upi)) {
            flash('err', 'UPI ID looks wrong. It should look like name@bank.');
            redirect('settings.php');
        }
        $vals = [
            'hostel_name' => trim((string)($_POST['hostel_name'] ?? '')) ?: 'Hostel',
            'upi_id' => $upi,
            'payee_name' => trim((string)($_POST['payee_name'] ?? '')) ?: 'Hostel Mess',
            'default_fee' => (string)num($_POST['default_fee'] ?? 0),
            'due_day' => (string)min(28, max(1, (int)($_POST['due_day'] ?? 20))),
        ];
        foreach (['t_breakfast', 't_lunch', 't_snack', 't_dinner'] as $k) {
            $vals[$k] = trim((string)($_POST[$k] ?? ''));
        }
        $st = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        foreach ($vals as $k => $v) {
            $st->execute([$k, $v]);
        }
        flash('ok', 'Settings saved.');
    } elseif (($_POST['action'] ?? '') === 'password') {
        $st = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $st->execute([$_SESSION['admin_id']]);
        $hash = $st->fetchColumn();
        $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), (string)$hash)) {
            flash('err', 'Current password is wrong.');
        } elseif (strlen($new) < 8) {
            flash('err', 'New password must be at least 8 characters.');
        } elseif ($new !== (string)($_POST['confirm_password'] ?? '')) {
            flash('err', 'New passwords do not match.');
        } else {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            flash('ok', 'Password changed.');
        }
    }
    redirect('settings.php');
}

$title = 'Settings';
require __DIR__ . '/_header.php';
?>
<div class="stack">
    <article class="panel">
        <div class="panel-header"><div><p class="heading">Portal</p><h3>Settings</h3></div></div>
        <form method="post" action="settings.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="settings">
            <div class="form-grid">
                <div class="wide"><label for="hostel_name">College / hostel name</label><input id="hostel_name" name="hostel_name" value="<?= e(setting('hostel_name')) ?>" required></div>
                <div><label for="upi_id">UPI ID for payments</label><input id="upi_id" name="upi_id" value="<?= e(setting('upi_id')) ?>" required></div>
                <div><label for="payee_name">Name shown in UPI app</label><input id="payee_name" name="payee_name" value="<?= e(setting('payee_name')) ?>" required></div>
                <div><label for="default_fee">Default monthly fee (Rs.)</label><input id="default_fee" name="default_fee" type="number" min="0" step="0.01" value="<?= e(setting('default_fee')) ?>"></div>
                <div><label for="due_day">Usual last day to pay (1-28)</label><input id="due_day" name="due_day" type="number" min="1" max="28" value="<?= e(setting('due_day', '20')) ?>"></div>
                <div><label for="t_breakfast">Breakfast timing</label><input id="t_breakfast" name="t_breakfast" value="<?= e(setting('t_breakfast')) ?>"></div>
                <div><label for="t_lunch">Lunch timing</label><input id="t_lunch" name="t_lunch" value="<?= e(setting('t_lunch')) ?>"></div>
                <div><label for="t_snack">Evening snack timing</label><input id="t_snack" name="t_snack" value="<?= e(setting('t_snack')) ?>"></div>
                <div><label for="t_dinner">Dinner timing</label><input id="t_dinner" name="t_dinner" value="<?= e(setting('t_dinner')) ?>"></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Save settings</button></div>
        </form>
    </article>

    <article class="panel">
        <div class="panel-header"><div><p class="heading">Security</p><h3>Change warden password</h3></div></div>
        <form method="post" action="settings.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <div class="form-grid">
                <div><label for="cp">Current password</label><input id="cp" name="current_password" type="password" autocomplete="current-password" required></div>
                <div><label for="np">New password (8+ characters)</label><input id="np" name="new_password" type="password" autocomplete="new-password" minlength="8" required></div>
                <div><label for="cf">Confirm new password</label><input id="cf" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Change password</button></div>
        </form>
    </article>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
