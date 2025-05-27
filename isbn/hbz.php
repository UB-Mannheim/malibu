<?php
// Redirect
$url = str_replace('/hbz.php', '/alma-sru.php', $_SERVER['REQUEST_URI']) . "&bibliothek=DE-HBZ";
header('Location: '. $url);
