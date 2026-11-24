<?php
require __DIR__ . '/../config.php';
if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify((string)($_POST['password'] ?? ''), $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$a['id'];
        redirect('index.php');
    }
    usleep(400000);
    $error = 'Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Login</title>
    <link rel="stylesheet" href="../user_interface/userinterface.css">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <main class="admin-login">
        <form class="panel" method="post" action="login.php">
            <p class="heading">Warden Panel</p>
            <h2>Sign in</h2>
            <?php if ($error): ?><p class="notice err"><?= e($error) ?></p><?php endif; ?>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Login</button></div>
        </form>
    </main>
</body>

</html>
