<?php
require __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $roll = trim($_POST['roll_no'] ?? '');
    $code = normalize_code((string)($_POST['code'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    $pdo = db();

    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Both passwords must match.';
    } else {
        $generic = 'Roll number or activation code is not valid, or the code has expired. Ask the warden for a new code.';
        $st = $pdo->prepare('SELECT id, password_hash, activation_code_hash, activation_expires, activation_attempts FROM students WHERE roll_no = ?');
        $st->execute([$roll]);
        $s = $st->fetch();

        $usable = $s && !$s['password_hash'] && $s['activation_code_hash']
            && (int)$s['activation_attempts'] < ACTIVATION_MAX_TRIES
            && strtotime((string)$s['activation_expires']) > time();

        if (!$usable) {
            usleep(400000);
            $error = ($s && $s['password_hash']) ? 'An account already exists for this roll number. Please login.' : $generic;
        } elseif (!password_verify($code, $s['activation_code_hash'])) {
            $pdo->prepare('UPDATE students SET activation_attempts = activation_attempts + 1 WHERE id = ?')->execute([$s['id']]);
            usleep(400000);
            $error = $generic;
        } else {
            // Save the password and kill the code in one statement, so a code can never be used twice.
            $up = $pdo->prepare('UPDATE students SET password_hash = ?, activation_code_hash = NULL, activation_expires = NULL, activation_attempts = 0
                                 WHERE id = ? AND password_hash IS NULL AND activation_code_hash IS NOT NULL');
            $up->execute([password_hash($pass, PASSWORD_DEFAULT), $s['id']]);
            if ($up->rowCount() === 1) {
                flash('ok', 'Account created. You can login now.');
                redirect('login.php');
            }
            $error = $generic;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account - Hostel Mess</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <main class="login-page">
        <section class="brand-panel" aria-label="Hostel Mess welcome">
            <div class="brand-content">
                <p class="eyebrow">Hostel Mess</p>
                <h1>Create your account</h1>
                <p>Use the roll number and one-time activation code the warden gave you, then choose your own password</p>
            </div>
        </section>

        <section class="login-panel" aria-label="Create account form">
            <form class="login-card" method="post" action="register.php" autocomplete="off">
                <div class="form-header">
                    <p class="eyebrow">New Student</p>
                    <h2>Activate your account</h2>
                </div>

                <?php if ($error): ?><p class="notice err"><?= e($error) ?></p><?php endif; ?>
                <?= csrf_field() ?>

                <label for="roll_no">Roll number</label>
                <input id="roll_no" name="roll_no" type="text" placeholder="Enter your roll number"
                    value="<?= e($_POST['roll_no'] ?? '') ?>" required>

                <label for="code">Activation code</label>
                <input id="code" name="code" type="text" placeholder="XXXX-XXXX" maxlength="12"
                    style="text-transform: uppercase; letter-spacing: 0.12em;" required>

                <label for="password">New password</label>
                <input id="password" name="password" type="password" placeholder="At least 6 characters"
                    autocomplete="new-password" minlength="6" required>

                <label for="confirm">Confirm password</label>
                <input id="confirm" name="confirm" type="password" placeholder="Type the password again"
                    autocomplete="new-password" minlength="6" required>

                <p class="hint">The code works once and expires in <?= ACTIVATION_DAYS ?> days.</p>

                <button type="submit" class="submit">Create account</button>

                <p class="signup-text">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </section>
    </main>
</body>

</html>
