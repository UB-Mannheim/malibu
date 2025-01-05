<?php
// Redirect
$url = str_replace('/swiss.php', '/alma-sru.php', $_SERVER['REQUEST_URI']) . "&bibliothek=CH-SWISS";
header('Location: '. $url);
