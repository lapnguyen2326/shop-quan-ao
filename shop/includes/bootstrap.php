<?php
session_start();
$CFG = require __DIR__ . '/../config.php';
// BASE = đường dẫn gốc của website (tự nhận, dù đặt trong thư mục con)
define('BASE', preg_replace('#/admin$#', '', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/')));
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
checkCsrf();
