<?php
require __DIR__ . '/../config.php';
admin_required();
$pdo = db();

$month = (string)($_REQUEST['month'] ?? date('Y-m'));
if (!valid_month($month)) {
    $month = date('Y-m');
}
$status = (string)($_REQUEST['status'] ?? 'all');
if (!in_array($status, ['all', 'pending', 'paid'], true)) {
    $status = 'all';
}
$first = $month . '-01';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'generate') {
        $dueDate = (string)($_POST['due_date'] ?? '');
        if (!valid_date($dueDate)) {
            flash('err', 'Please pick a valid due date.');
        } else {
            $st = $pdo->prepare('INSERT IGNORE INTO dues (student_id, month, base_amount, due_date) SELECT id, ?, monthly_fee, ? FROM students');
            $st->execute([$first, $dueDate]);
            flash('ok', $st->rowCount() . ' bill(s) created for ' . month_label($first) . ' (students who already had one were skipped).');
        }
    } elseif ($action === 'deduct_all') {
        $st = $pdo->prepare("UPDATE dues SET deduction = LEAST(?, base_amount), deduction_reason = NULLIF(?, '') WHERE month = ? AND status = 'pending'");
        $st->execute([num($_POST['amount'] ?? 0), trim((string)($_POST['reason'] ?? '')), $first]);
        flash('ok', 'Deduction applied to ' . $st->rowCount() . ' pending bill(s) of ' . month_label($first) . '.');
    } elseif ($action === 'update' && $id) {
        $dueDate = (string)($_POST['due_date'] ?? '');
        if (!valid_date($dueDate)) {
            flash('err', 'Invalid due date.');
        } else {
            $pdo->prepare("UPDATE dues SET deduction = LEAST(?, base_amount), deduction_reason = NULLIF(?, ''), fine = ?, due_date = ? WHERE id = ?")
                ->execute([num($_POST['deduction'] ?? 0), trim((string)($_POST['reason'] ?? '')), num($_POST['fine'] ?? 0), $dueDate, $id]);
            flash('ok', 'Bill updated.');
        }
    } elseif ($action === 'paid' && $id) {
        $pdo->prepare("UPDATE dues SET status = 'paid', paid_on = CURDATE() WHERE id = ?")->execute([$id]);
        flash('ok', 'Marked as paid.');
    } elseif ($action === 'unpaid' && $id) {
        $pdo->prepare("UPDATE dues SET status = 'pending', paid_on = NULL WHERE id = ?")->execute([$id]);
        flash('ok', 'Marked as unpaid.');
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM dues WHERE id = ?')->execute([$id]);
        flash('ok', 'Bill deleted.');
    }

    if (($_POST['back'] ?? '') === 'index') {
        redirect('index.php');
    }
    redirect('dues.php?' . http_build_query(['month' => $month, 'status' => $status]));
}

$sql = 'SELECT d.*, (d.base_amount - d.deduction + d.fine) AS total, s.roll_no, s.name FROM dues d JOIN students s ON s.id = d.student_id WHERE d.month = ?';
$args = [$first];
if ($status !== 'all') {
    $sql .= ' AND d.status = ?';
    $args[] = $status;
}
$st = $pdo->prepare($sql . ' ORDER BY s.roll_no');
$st->execute($args);
$rows = $st->fetchAll();
$sumPending = 0.0;
$sumPaid = 0.0;
foreach ($rows as $r) {
    if ($r['status'] === 'paid') { $sumPaid += (float)$r['total']; } else { $sumPending += (float)$r['total']; }
}
$defaultDue = $month . '-' . str_pad((string)min(28, max(1, (int)setting('due_day', '20'))), 2, '0', STR_PAD_LEFT);

$title = 'Dues';
require __DIR__ . '/_header.php';
?>
<div class="stack">
    <article class="panel">
        <div class="panel-header"><div><p class="heading">Month</p><h3>Choose month</h3></div></div>
        <form method="get" action="dues.php" class="form-grid">
            <div><label for="month">Month</label><input id="month" name="month" type="month" value="<?= e($month) ?>"></div>
            <div><label for="status">Show</label>
                <select id="status" name="status">
                    <?php foreach (['all' => 'All bills', 'pending' => 'Pending only', 'paid' => 'Paid only'] as $k => $l): ?>
                        <option value="<?= $k ?>"<?= $status === $k ? ' selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button class="btn" type="submit">Show</button></div>
        </form>
    </article>

    <section class="content-grid">
        <article class="panel">
            <div class="panel-header"><div><p class="heading">Step 1</p><h3>Create bills for <?= e(month_label($first)) ?></h3></div></div>
            <p class="muted">Makes one bill per student using each student's monthly fee. Students who already have a bill for this month are skipped, so it is safe to run again after adding new students.</p>
            <form method="post" action="dues.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                <div class="form-grid"><div><label for="due_date">Last date to pay</label><input id="due_date" name="due_date" type="date" value="<?= e($defaultDue) ?>" required></div></div>
                <div class="form-actions"><button class="btn" type="submit">Create bills</button></div>
            </form>
        </article>

        <article class="panel">
            <div class="panel-header"><div><p class="heading">Vacation / holiday</p><h3>Deduct fee for everyone</h3></div></div>
            <p class="muted">For Holi, Diwali or a semester break: you decide the amount. It is set on every <strong>pending</strong> bill of <?= e(month_label($first)) ?> and students see it in their profile. Enter 0 to remove it.</p>
            <form method="post" action="dues.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="deduct_all">
                <input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                <div class="form-grid">
                    <div><label for="amount">Deduction per student (Rs.)</label><input id="amount" name="amount" type="number" min="0" step="0.01" required></div>
                    <div><label for="reason">Reason</label><input id="reason" name="reason" maxlength="150" placeholder="e.g. Diwali vacation"></div>
                </div>
                <div class="form-actions"><button class="btn" type="submit" onclick="return confirm('Apply this deduction to all pending bills of this month?');">Apply deduction</button></div>
            </form>
        </article>
    </section>

    <article class="panel">
        <div class="panel-header"><div><p class="heading"><?= e(month_label($first)) ?></p><h3><?= count($rows) ?> bill(s)</h3></div>
            <span class="note">Pending <?= e(money($sumPending)) ?> &middot; Paid <?= e(money($sumPaid)) ?></span></div>
        <div class="scroll">
            <table class="admin-table">
                <thead><tr><th>Student</th><th class="num">Fee</th><th class="num">Deduction</th><th class="num">Fine</th><th class="num">Total</th><th>Last date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['roll_no']) ?><br><small class="note"><?= e($r['name']) ?></small></td>
                        <td class="num"><?= e(money($r['base_amount'])) ?></td>
                        <td class="num"><?= (float)$r['deduction'] > 0 ? e(money($r['deduction'])) : '-' ?><?= $r['deduction_reason'] ? '<small class="note">' . e($r['deduction_reason']) . '</small>' : '' ?></td>
                        <td class="num"><?= (float)$r['fine'] > 0 ? e(money($r['fine'])) : '-' ?></td>
                        <td class="num"><strong><?= e(money($r['total'])) ?></strong></td>
                        <td><?= e(fmt_date($r['due_date'])) ?></td>
                        <td><span class="pill <?= e($r['status']) ?>"><?= $r['status'] === 'paid' ? 'Paid' : 'Pending' ?></span><?= $r['paid_on'] ? '<small class="note">' . e(fmt_date($r['paid_on'])) . '</small>' : '' ?></td>
                        <td>
                            <div class="btn-row">
                                <form method="post" action="dues.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="<?= $r['status'] === 'paid' ? 'unpaid' : 'paid' ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                                    <button class="btn small<?= $r['status'] === 'paid' ? ' ghost' : '' ?>" type="submit"><?= $r['status'] === 'paid' ? 'Mark unpaid' : 'Mark paid' ?></button>
                                </form>
                                <details class="edit-box">
                                    <summary>Edit</summary>
                                    <form method="post" action="dues.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                                        <div class="form-grid">
                                            <div><label>Deduction (Rs.)</label><input name="deduction" type="number" min="0" step="0.01" value="<?= e($r['deduction']) ?>"></div>
                                            <div><label>Reason</label><input name="reason" maxlength="150" value="<?= e($r['deduction_reason']) ?>"></div>
                                            <div><label>Late fine (Rs.)</label><input name="fine" type="number" min="0" step="0.01" value="<?= e($r['fine']) ?>"></div>
                                            <div><label>Last date</label><input name="due_date" type="date" value="<?= e($r['due_date']) ?>" required></div>
                                        </div>
                                        <div class="form-actions"><button class="btn small" type="submit">Save</button></div>
                                    </form>
                                </details>
                                <form method="post" action="dues.php" onsubmit="return confirm('Delete this bill?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                                    <button class="btn small danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="8">No bills here. Use "Create bills" above.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
