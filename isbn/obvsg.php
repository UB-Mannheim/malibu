<?php
// Redirect
$url = str_replace('/obvsg.php', '/alma-sru.php', $_SERVER['REQUEST_URI']) . "&bibliothek=AT-OBVSG";
header('Location: '. $url);
