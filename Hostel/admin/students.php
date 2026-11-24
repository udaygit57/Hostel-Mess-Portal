<?php
require __DIR__ . '/../config.php';
admin_required();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $f = array_map(fn($k) => trim((string)($_POST[$k] ?? '')), ['roll_no', 'name', 'course', 'branch', 'batch', 'room_no']);
        [$roll, $name, $course, $branch, $batch, $room] = $f;
        $fee = num($_POST['monthly_fee'] ?? 0);
        if ($roll === '' || $name === '' || $course === '' || $branch === '' || $room === '') {
            flash('err', 'Roll no, name, course, branch and room no are required.');
            redirect('students.php' . ($id ? '?edit=' . $id : ''));
        }
        try {
            if ($id) {
                $pdo->prepare('UPDATE students SET roll_no=?, name=?, course=?, branch=?, batch=?, room_no=?, monthly_fee=? WHERE id=?')
                    ->execute([$roll, $name, $course, $branch, $batch ?: null, $room, $fee, $id]);
                flash('ok', 'Student updated.');
            } else {
                $pdo->prepare('INSERT INTO students (roll_no, name, course, branch, batch, room_no, monthly_fee) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$roll, $name, $course, $branch, $batch ?: null, $room, $fee]);
                $_SESSION['new_code'] = ['roll' => $roll, 'name' => $name] + issue_activation_code($pdo, (int)$pdo->lastInsertId());
                flash('ok', 'Student added. Give them the activation code shown below.');
            }
        } catch (PDOException $ex) {
            flash('err', $ex->getCode() === '23000' ? 'That roll number already exists.' : 'Could not save the student.');
        }
    } elseif (in_array($action, ['reset', 'newcode'], true) && $id) {
        $st = $pdo->prepare('SELECT roll_no, name, password_hash FROM students WHERE id = ?');
        $st->execute([$id]);
        $s = $st->fetch();
        if ($s && $action === 'newcode' && $s['password_hash']) {
            flash('err', 'This student already has an account. Use "Reset password" if they forgot it.');
        } elseif ($s) {
            if ($action === 'reset') {
                $pdo->prepare('UPDATE students SET password_hash = NULL WHERE id = ?')->execute([$id]);
            }
            $_SESSION['new_code'] = ['roll' => $s['roll_no'], 'name' => $s['name']] + issue_activation_code($pdo, $id);
            flash('ok', 'New activation code created. Any older code no longer works.');
        }
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
        flash('ok', 'Student and all their bills deleted.');
    }
    redirect('students.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$v = $edit ?? ['id' => '', 'roll_no' => '', 'name' => '', 'course' => '', 'branch' => '', 'batch' => '', 'room_no' => '', 'monthly_fee' => setting('default_fee', '2700')];
$list = $pdo->query('SELECT * FROM students ORDER BY roll_no')->fetchAll();

$newCode = $_SESSION['new_code'] ?? null;
unset($_SESSION['new_code']);
if ($newCode) {
    header('Cache-Control: no-store');
}

$title = 'Students';
require __DIR__ . '/_header.php';
?>
<div class="stack">
    <?php if ($newCode): ?>
    <article class="panel code-box">
        <p class="heading">One-time activation code</p>
        <h3><?= e($newCode['name']) ?> &middot; Roll no <?= e($newCode['roll']) ?></h3>
        <p class="code-value" id="codeValue"><?= e($newCode['code']) ?></p>
        <p class="muted">Share this code privately with the student (in person or a private message). It is shown <strong>only once</strong>, works once, and is valid until <?= e(date('d M Y, h:i A', strtotime($newCode['expires']))) ?>. If it is lost, press "New code".</p>
        <button class="btn small ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('codeValue').textContent).then(()=>this.textContent='Copied')">Copy code</button>
    </article>
    <?php endif; ?>

    <article class="panel">
        <div class="panel-header"><div><p class="heading"><?= $edit ? 'Edit student' : 'Add student' ?></p><h3><?= $edit ? e($edit['name']) : 'New student' ?></h3></div></div>
        <form method="post" action="students.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e($v['id']) ?>">
            <div class="form-grid">
                <div><label for="roll_no">Roll no</label><input id="roll_no" name="roll_no" value="<?= e($v['roll_no']) ?>" required></div>
                <div><label for="name">Name</label><input id="name" name="name" value="<?= e($v['name']) ?>" required></div>
                <div><label for="course">Course</label><input id="course" name="course" value="<?= e($v['course']) ?>" placeholder="e.g. Diploma" required></div>
                <div><label for="branch">Branch</label><input id="branch" name="branch" value="<?= e($v['branch']) ?>" required></div>
                <div><label for="batch">Batch (optional)</label><input id="batch" name="batch" value="<?= e($v['batch']) ?>" placeholder="e.g. 2024-27"></div>
                <div><label for="room_no">Room no</label><input id="room_no" name="room_no" value="<?= e($v['room_no']) ?>" required></div>
                <div><label for="monthly_fee">Monthly mess fee (Rs.)</label><input id="monthly_fee" name="monthly_fee" type="number" min="0" step="0.01" value="<?= e($v['monthly_fee']) ?>" required></div>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add student' ?></button>
                <?php if ($edit): ?><a class="btn ghost" href="students.php">Cancel</a><?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel">
        <div class="panel-header"><div><p class="heading">All students</p><h3><?= count($list) ?> in hostel</h3></div></div>
        <div class="scroll">
            <table class="admin-table">
                <thead><tr><th>Roll no</th><th>Name</th><th>Course / Branch</th><th>Room</th><th class="num">Fee</th><th>Account / code</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= e($r['roll_no']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['course']) ?> / <?= e($r['branch']) ?><?= $r['batch'] ? ' (' . e($r['batch']) . ')' : '' ?></td>
                        <td><?= e($r['room_no']) ?></td>
                        <td class="num"><?= e(money($r['monthly_fee'])) ?></td>
                        <td>
                            <?php
                            if ($r['password_hash']) { $label = 'Account created'; $cls = 'paid'; }
                            elseif (!$r['activation_code_hash']) { $label = 'No code'; $cls = 'pending'; }
                            elseif ((int)$r['activation_attempts'] >= ACTIVATION_MAX_TRIES) { $label = 'Code locked'; $cls = 'pending'; }
                            elseif (strtotime((string)$r['activation_expires']) < time()) { $label = 'Code expired'; $cls = 'pending'; }
                            else { $label = 'Code active'; $cls = 'paid'; }
                            ?>
                            <span class="pill <?= $cls ?>"><?= $label ?></span>
                            <?php if (!$r['password_hash'] && $label === 'Code active'): ?><small class="note">until <?= e(date('d M', strtotime($r['activation_expires']))) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-row">
                                <a class="btn small ghost" href="students.php?edit=<?= (int)$r['id'] ?>">Edit</a>
                                <?php if (!$r['password_hash']): ?>
                                    <form method="post" action="students.php">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="newcode"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn small" type="submit">New code</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="students.php" onsubmit="return confirm('Clear this student\'s password and create a new activation code?');">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn small ghost" type="submit">Reset password</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="students.php" onsubmit="return confirm('Delete this student and all their bills?');">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn small danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?><tr><td colspan="7">No students yet. Add the first one above.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
