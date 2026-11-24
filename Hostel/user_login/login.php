<?php
require __DIR__ . '/../config.php';

if (!empty($_SESSION['student_id'])) {
    redirect('../user_interface/userinterface.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $roll = trim($_POST['roll_no'] ?? '');
    $st = db()->prepare('SELECT id, password_hash FROM students WHERE roll_no = ?');
    $st->execute([$roll]);
    $s = $st->fetch();

    if ($s && !$s['password_hash']) {
        $error = 'This roll number has no account yet. Use "Create an account" and enter the activation code from the warden.';
    } elseif ($s && password_verify($_POST['password'] ?? '', $s['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['student_id'] = (int)$s['id'];
        redirect('../user_interface/userinterface.php');
    } else {
        usleep(400000);
        $error = 'Roll number or password is wrong.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Mess</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <main class="login-page">
        <section class="brand-panel" aria-label="Hostel Mess welcome">
            <div class="brand-content">
                <p class="eyebrow">Hostel Mess</p>
                <h1>Warmly Welcoming you</h1>
                <p>Sign in to see your Hostel Mess Dues</p>
            </div>
        </section>

        <section class="login-panel" aria-label="Login form">
            <form class="login-card" method="post" action="login.php">
                <div class="form-header">
                    <p class="eyebrow">Student Login</p>
                    <h2>Sign in to your account</h2>
                </div>

                <?= flash_show() ?>
                <?php if ($error): ?><p class="notice err"><?= e($error) ?></p><?php endif; ?>
                <?= csrf_field() ?>

                <label for="roll_no">Roll number</label>
                <input id="roll_no" name="roll_no" type="text" placeholder="Enter your roll number"
                    value="<?= e($_POST['roll_no'] ?? '') ?>" autocomplete="username" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Enter password"
                    autocomplete="current-password" required>

                <p class="hint">Forgot your password? Ask the warden to reset it. You will get a new activation code to set a new password.</p>

                <button type="submit" class="submit">Login</button>

                <p class="signup-text">New student? <a href="register.php">Create an account</a></p>
            </form>
        </section>
    </main>
</body>

</html>
