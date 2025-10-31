<?php
require __DIR__ . '/../includes/init.php';

unset($_SESSION['service_logged_in'], $_SESSION['service_id'], $_SESSION['service_name']);
session_regenerate_id(true);

redirect('/service/login.php');
