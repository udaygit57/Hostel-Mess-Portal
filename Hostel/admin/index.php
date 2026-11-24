<?php
require __DIR__ . '/../config.php';
admin_required();
$pdo = db();

$students = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$registered = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE password_hash IS NOT NULL')->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM dues WHERE status = 'pending'")->fetchColumn();
$pendingAmt = (float)$pdo->query("SELECT COALESCE(SUM(base_amount - deduction + fine), 0) FROM dues WHERE status = 'pending'")->fetchColumn();
$collected = (float)$pdo->query("SELECT COALESCE(SUM(base_amount - deduction + fine), 0) FROM dues WHERE status = 'paid' AND paid_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

$rows = $pdo->query("SELECT d.id, d.month, d.due_date, (d.base_amount - d.deduction + d.fine) AS total, s.roll_no, s.name
    FROM dues d JOIN students s ON s.id = d.student_id
    WHERE d.status = 'pending' ORDER BY d.due_date, s.roll_no LIMIT 15")->fetchAll();

$title = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<div class="stack">
    <section class="stat-grid">
        <article class="summary-card"><span>Students</span><strong><?= $students ?></strong><p><?= $registered ?> have created an account</p></article>
        <article class="summary-card due-card"><span>Pending amount</span><strong><?= e(money($pendingAmt)) ?></strong><p><?= $pendingCount ?> unpaid bill<?= $pendingCount === 1 ? '' : 's' ?></p></article>
        <article class="summary-card"><span>Collected this month</span><strong><?= e(money($collected)) ?></strong><p>Bills marked paid</p></article>
        <article class="summary-card"><span>Quick start</span><p><a class="inline-link" href="dues.php">Create this month's bills</a></p><p><a class="inline-link" href="students.php">Add a student</a></p></article>
    </section>

    <article class="panel">
        <div class="panel-header"><div><p class="heading">Unpaid</p><h3>Oldest pending bills</h3></div></div>
        <div class="scroll">
            <table class="admin-table">
                <thead><tr><th>Roll no</th><th>Name</th><th>Month</th><th class="num">Total</th><th>Due date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['roll_no']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e(month_label($r['month'])) ?></td>
                        <td class="num"><?= e(money($r['total'])) ?></td>
                        <td><?= e(fmt_date($r['due_date'])) ?></td>
                        <td>
                            <form method="post" action="dues.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="paid">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="back" value="index">
                                <button class="btn small" type="submit">Mark paid</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="6">No pending bills.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
