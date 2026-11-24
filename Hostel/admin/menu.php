<?php
require __DIR__ . '/../config.php';
admin_required();
$pdo = db();
$days = menu_days();
$meals = menu_meals();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $up = $pdo->prepare('INSERT INTO menu (day_no, meal, items) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE items = VALUES(items)');
    $del = $pdo->prepare('DELETE FROM menu WHERE day_no = ? AND meal = ?');
    foreach ($days as $no => $_) {
        foreach ($meals as $key => $__) {
            $items = trim((string)($_POST['m'][$no][$key] ?? ''));
            if ($items === '') {
                $del->execute([$no, $key]);
            } else {
                $up->execute([$no, $key, cut($items, 255)]);
            }
        }
    }
    flash('ok', 'Menu saved. Students see the new menu right away.');
    redirect('menu.php');
}

$menu = [];
foreach ($pdo->query('SELECT day_no, meal, items FROM menu')->fetchAll() as $r) {
    $menu[(int)$r['day_no']][$r['meal']] = $r['items'];
}

$title = 'Mess Menu';
require __DIR__ . '/_header.php';
?>
<article class="panel">
    <div class="panel-header"><div><p class="heading">Weekly chart</p><h3>Edit mess menu</h3></div></div>
    <p class="muted">Separate items with commas. Leave a box empty to show "-" for that meal. Meal timings are in <a class="inline-link" href="settings.php">Settings</a>.</p>
    <form method="post" action="menu.php">
        <?= csrf_field() ?>
        <div class="scroll">
            <table class="admin-table menu-edit" style="min-width:900px">
                <thead><tr><th>Day</th><?php foreach ($meals as [$label, $t]): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($days as $no => $dayName): ?>
                    <tr>
                        <th><?= e($dayName) ?></th>
                        <?php foreach ($meals as $key => $m): ?>
                            <td><input name="m[<?= $no ?>][<?= $key ?>]" maxlength="255" value="<?= e($menu[$no][$key] ?? '') ?>" aria-label="<?= e($dayName . ' ' . $m[0]) ?>"></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions"><button class="btn" type="submit">Save menu</button></div>
    </form>
</article>
<?php require __DIR__ . '/_footer.php'; ?>
