<?php
// Expects $title to be set. Pages call admin_required() before including this file.
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = ['index.php' => 'Dashboard', 'students.php' => 'Students', 'dues.php' => 'Dues',
        'menu.php' => 'Mess Menu', 'settings.php' => 'Settings'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Warden Panel</title>
    <link rel="stylesheet" href="../user_interface/userinterface.css">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Warden Panel</p>
            <h1><?= e(setting('hostel_name', 'Hostel')) ?></h1>
        </div>
        <nav class="admin-nav">
            <?php foreach ($nav as $file => $label): ?>
                <a class="logout-link<?= $current === $file ? ' active' : '' ?>" href="<?= $file ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <a class="logout-link" href="logout.php">Logout</a>
        </nav>
    </header>
    <main class="dashboard">
        <?= flash_show() ?>
