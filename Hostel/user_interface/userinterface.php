<?php
require __DIR__ . '/../config.php';
$s = student_required();

$st = db()->prepare('SELECT *, (base_amount - deduction + fine) AS total FROM dues WHERE student_id = ? ORDER BY month DESC');
$st->execute([$s['id']]);
$all = $st->fetchAll();

$pending = array_values(array_filter($all, fn($d) => $d['status'] === 'pending'));
$paid = array_values(array_filter($all, fn($d) => $d['status'] === 'paid'));
usort($paid, fn($a, $b) => strcmp($b['paid_on'] . $b['month'], $a['paid_on'] . $a['month']));
$last = $paid[0] ?? null;

$totalDue = array_sum(array_map(fn($d) => (float)$d['total'], $pending));
$nextDue = null;
foreach ($pending as $d) {
    if ($nextDue === null || $d['due_date'] < $nextDue) {
        $nextDue = $d['due_date'];
    }
}
$overdue = $nextDue !== null && $nextDue < date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Mess Dues</title>
    <link rel="stylesheet" href="userinterface.css">
</head>

<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Hostel Mess Portal</p>
            <h1><?= e(setting('hostel_name', 'Hostel')) ?></h1>
        </div>
        <div class="topbar-actions">
            <a class="logout-link" href="menu.php">Mess Menu</a>
            <a class="logout-link" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="dashboard">
        <?= flash_show() ?>
        <section class="welcome-section">
            <div class="profile">
                <p class="heading">Student Account</p>
                <h2><?= e($s['name']) ?><?= $s['batch'] ? ' ' . e('(' . $s['batch'] . ')') : '' ?></h2>
                <p>Check your current hostel mess dues and payment status.</p>
            </div>
            <div class="student-card">
                <div class="student-table">
                    <table>
                        <thead>
                            <tr>
                                <td>Roll No</td>
                                <td>Course</td>
                                <td>Branch</td>
                                <td>Room No</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th><?= e($s['roll_no']) ?></th>
                                <th><?= e($s['course']) ?></th>
                                <th><?= e($s['branch']) ?></th>
                                <th><?= e($s['room_no']) ?></th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="summary-grid">
            <article class="summary-card due-card">
                <span>Total Due</span>
                <strong><?= e(money($totalDue)) ?></strong>
                <p><?= $pending ? ($overdue ? 'Was due on ' : 'Pay before ') . e(fmt_date($nextDue)) : 'Nothing to pay right now' ?></p>
            </article>
            <article class="summary-card">
                <span>Last Payment</span>
                <strong><?= $last ? e(money($last['total'])) : '-' ?></strong>
                <p><?= $last ? 'Paid on ' . e(fmt_date($last['paid_on'])) : 'No payment recorded yet' ?></p>
            </article>
            <article class="summary-card">
                <span>Status</span>
                <?php if ($pending): ?>
                    <strong class="status-text">Pending</strong>
                    <p><?= count($pending) ?> unpaid bill<?= count($pending) > 1 ? 's' : '' ?></p>
                <?php else: ?>
                    <strong class="status-ok">All clear</strong>
                    <p>No unpaid bills</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="content-grid">
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <p class="heading">Current Bill</p>
                        <h3><?= $pending ? 'Mess Dues' : 'No dues' ?></h3>
                    </div>
                    <?php if ($pending): ?><span class="badge">Unpaid</span><?php endif; ?>
                </div>

                <?php if (!$pending): ?>
                    <p class="muted">You have paid everything. New bills appear here when the warden adds them.</p>
                <?php endif; ?>

                <?php foreach ($pending as $d): ?>
                    <div class="bill-block">
                        <p class="bill-month"><?= e(month_label($d['month'])) ?> &middot; due <?= e(fmt_date($d['due_date'])) ?></p>
                        <div class="bill-list">
                            <div class="bill-row">
                                <span>Monthly mess charge</span>
                                <strong><?= e(money($d['base_amount'])) ?></strong>
                            </div>
                            <?php if ((float)$d['deduction'] > 0): ?>
                                <div class="bill-row">
                                    <span>Deduction<?= $d['deduction_reason'] ? ' (' . e($d['deduction_reason']) . ')' : '' ?></span>
                                    <strong>- <?= e(money($d['deduction'])) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="bill-row">
                                <span>Late fine</span>
                                <strong><?= e(money($d['fine'])) ?></strong>
                            </div>
                            <div class="bill-row total">
                                <span>Total payable</span>
                                <strong><?= e(money($d['total'])) ?></strong>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="pay.php?id=<?= (int)$d['id'] ?>" class="qr">Pay Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </article>

            <article class="panel">
                <div class="panel-header">
                    <div>
                        <p class="heading">history</p>
                        <h3>Dues of recent Months</h3>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($all, 0, 12) as $d): ?>
                                <tr>
                                    <td><?= e(month_label($d['month'])) ?></td>
                                    <td>
                                        <?= e(money($d['total'])) ?>
                                        <?php if ((float)$d['deduction'] > 0): ?>
                                            <small class="note">(<?= e(money($d['deduction'])) ?> deducted<?= $d['deduction_reason'] ? ': ' . e($d['deduction_reason']) : '' ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="pill <?= e($d['status']) ?>"><?= $d['status'] === 'paid' ? 'Paid' : 'Pending' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$all): ?>
                                <tr><td colspan="3">No bills yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>
</body>

</html>
