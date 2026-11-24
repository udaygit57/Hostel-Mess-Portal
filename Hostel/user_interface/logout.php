<?php
require __DIR__ . '/../config.php';
unset($_SESSION['student_id']);
session_regenerate_id(true);
redirect('../user_login/login.php');
