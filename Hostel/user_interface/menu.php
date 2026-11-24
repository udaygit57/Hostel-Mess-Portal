<?php
require __DIR__ . '/../config.php';
$s = student_required();

$menu = [];
foreach (db()->query('SELECT day_no, meal, items FROM menu')->fetchAll() as $r) {
    $menu[(int)$r['day_no']][$r['meal']] = $r['items'];
}
$days = menu_days();
$meals = menu_meals();
$today = (int)date('N');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Menu</title>
    <link rel="stylesheet" href="userinterface.css">
</head>

<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Hostel Mess Portal</p>
            <h1><?= e(setting('hostel_name', 'Hostel')) ?></h1>
        </div>
        <div class="topbar-actions">
            <a class="logout-link" href="userinterface.php">My Dues</a>
            <a class="logout-link" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="dashboard">
        <section class="welcome-section">
            <div class="profile">
                <p class="heading">Mess Menu 2026-27</p>
                <h2>Today's Menu</h2>
                <p><?= e($days[$today]) ?></p>
            </div>
        </section>

        <section class="today-grid">
            <?php foreach ($meals as $key => [$label, $timeKey]): ?>
                <article class="summary-card">
                    <span><?= e($label) ?></span>
                    <small class="note"><?= e(setting($timeKey)) ?></small>
                    <p class="meal-items"><?= e($menu[$today][$key] ?? '-') ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <article class="panel">
            <div class="panel-header">
                <div>
                    <p class="heading">Weekly chart</p>
                    <h3>Menu for the whole week</h3>
                </div>
            </div>
            <div class="table-wrap menu-wrap">
                <table class="menu-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <?php foreach ($meals as [$label, $timeKey]): ?>
                                <th><?= e($label) ?><br><small><?= e(setting($timeKey)) ?></small></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $no => $dayName): ?>
                            <tr class="<?= $no === $today ? 'today-row' : '' ?>">
                                <th class="day-cell"><?= e($dayName) ?></th>
                                <?php foreach ($meals as $key => $m): ?>
                                    <td><?= e($menu[$no][$key] ?? '-') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </main>
</body>

</html>
