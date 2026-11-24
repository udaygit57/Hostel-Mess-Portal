<?php
declare(strict_types=1);

// ---- Database settings (XAMPP defaults) ----
const DB_HOST = 'localhost';
const DB_NAME = 'hostel_db';
const DB_USER = 'root';
const DB_PASS = '';

const ACTIVATION_DAYS = 7;      // how long a code stays valid
const ACTIVATION_MAX_TRIES = 5; // wrong guesses before the code is locked

date_default_timezone_set('Asia/Kolkata');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Database connection failed. Start MySQL in XAMPP and import database.sql (see README.txt).');
        }
    }
    return $pdo;
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ---- CSRF ----
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void
{
    $ok = isset($_SESSION['csrf'], $_POST['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Session expired. Go back, refresh the page and try again.');
    }
}

// ---- Flash messages ----
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_show(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $out .= '<p class="notice ' . ($type === 'ok' ? 'ok' : 'err') . '">' . e($msg) . '</p>';
    }
    unset($_SESSION['flash']);
    return $out;
}

// ---- Settings ----
function setting(string $name, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = db()->query('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $all[$name] ?? $default;
}

// ---- Auth guards ----
function student_required(): array
{
    if (!empty($_SESSION['student_id'])) {
        $st = db()->prepare('SELECT * FROM students WHERE id = ?');
        $st->execute([$_SESSION['student_id']]);
        $s = $st->fetch();
        if ($s) {
            return $s;
        }
    }
    unset($_SESSION['student_id']);
    redirect('../user_login/login.php');
}

function admin_required(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}

// ---- Helpers ----
function money($n): string
{
    $s = number_format((float)$n, 2);
    if (substr($s, -3) === '.00') {
        $s = substr($s, 0, -3);
    }
    return 'Rs. ' . $s;
}

function fmt_date(?string $d): string
{
    return $d ? date('d M Y', strtotime($d)) : '-';
}

function month_label(string $d): string
{
    return date('F Y', strtotime($d));
}

function valid_date(string $d): bool
{
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x !== false && $x->format('Y-m-d') === $d;
}

function valid_month(string $m): bool
{
    return preg_match('/^\d{4}-\d{2}$/', $m) === 1 && valid_date($m . '-01');
}

function cut(string $s, int $n): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $n);
    }
    return implode('', array_slice(preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, $n));
}

function num($v): float
{
    return is_numeric($v) ? max(0.0, (float)$v) : 0.0;
}

function menu_days(): array
{
    return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
}

function menu_meals(): array
{
    return ['breakfast' => ['Breakfast', 't_breakfast'], 'lunch' => ['Lunch', 't_lunch'],
            'snack' => ['Evening snack', 't_snack'], 'dinner' => ['Dinner', 't_dinner']];
}

// ---- Activation codes (one-time, stored only as a hash) ----
function normalize_code(string $c): string
{
    return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $c));
}

function issue_activation_code(PDO $pdo, int $studentId): array
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I to avoid mix-ups
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $expires = date('Y-m-d H:i:s', strtotime('+' . ACTIVATION_DAYS . ' days'));
    $pdo->prepare('UPDATE students SET activation_code_hash = ?, activation_expires = ?, activation_attempts = 0 WHERE id = ?')
        ->execute([password_hash($code, PASSWORD_DEFAULT), $expires, $studentId]);
    return ['code' => substr($code, 0, 4) . '-' . substr($code, 4), 'expires' => $expires];
}
