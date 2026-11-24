<?php
require __DIR__ . '/../config.php';
$s = student_required();

$st = db()->prepare("SELECT *, (base_amount - deduction + fine) AS total FROM dues WHERE id = ? AND student_id = ? AND status = 'pending'");
$st->execute([(int)($_GET['id'] ?? 0), $s['id']]);
$d = $st->fetch();
if (!$d) {
    flash('err', 'That bill was not found or is already paid.');
    redirect('userinterface.php');
}

$upi = 'upi://pay?pa=' . setting('upi_id')
    . '&pn=' . rawurlencode(setting('payee_name'))
    . '&am=' . number_format((float)$d['total'], 2, '.', '')
    . '&cu=INR'
    . '&tn=' . rawurlencode('Mess ' . $s['roll_no'] . ' ' . date('M Y', strtotime($d['month'])));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Mess Dues</title>
    <link rel="stylesheet" href="userinterface.css">
</head>

<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Hostel Mess Portal</p>
            <h1><?= e(setting('hostel_name', 'Hostel')) ?></h1>
        </div>
        <div class="topbar-actions">
            <a class="logout-link" href="userinterface.php">Back to dues</a>
        </div>
    </header>

    <main class="dashboard">
        <article class="panel pay-panel">
            <p class="heading"><?= e(month_label($d['month'])) ?> mess dues</p>
            <h3>Pay <?= e(money($d['total'])) ?></h3>
            <p class="muted">Scan this QR with any UPI app (Google Pay, PhonePe, Paytm, BHIM). The amount is filled in for you.</p>

            <div id="qr" class="qr-box" aria-label="UPI payment QR code"></div>

            <p class="muted">Paying from this phone? <a class="inline-link" href="<?= e($upi) ?>">Open your UPI app</a></p>
            <p class="muted small">Paying to <strong><?= e(setting('payee_name')) ?></strong> (<?= e(setting('upi_id')) ?>).
                After you pay, the warden marks the bill as paid and it moves to your history.</p>
        </article>
    </main>

    <script src="../assets/qrcode.js"></script>
    <script>
        var qr = qrcode(0, 'M');
        qr.addData(<?= json_encode($upi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        qr.make();
        document.getElementById('qr').innerHTML = qr.createSvgTag({ cellSize: 6, margin: 3, scalable: true });
    </script>
</body>

</html>
