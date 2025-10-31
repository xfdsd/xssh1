<?php
require __DIR__ . '/../includes/init.php';

unset($_SESSION['admin_logged_in'], $_SESSION['admin_id']);
session_regenerate_id(true);

redirect('/admin/login.php');
