<?php
require_once 'config/db.php';
$_SESSION = [];
session_destroy();
header('Location: public/index.php');
exit;