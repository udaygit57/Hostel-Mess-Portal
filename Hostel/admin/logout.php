<?php
require __DIR__ . '/../config.php';
unset($_SESSION['admin_id']);
session_regenerate_id(true);
redirect('login.php');
