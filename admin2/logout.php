<?php
require_once __DIR__ . '/../shared/config.php';
session_destroy();
header('Location: ../login.php');
exit;
