<html>
<head>
</head>
<body>
<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
 *
 * Copyright (C) 2013 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Beschreibung: Wandelt eine Texteingabe um stellt den Texteingabe
 * als HTML-Seite dar. Dies ist insbesondere nützlich in Kombination
 * mit dem Mannheimer AutoLink Tool (Greasemonkey-Skript).
 */

echo "<h2>Texteingabe</h2>";
echo "<form action=\"showText.php\" method=\"post\">\n";

if (isset($_POST['text'])) {
    $n = $_POST['text'];
    echo '<textarea  name="text" cols="100" rows="20">' . $n . '</textarea>';
    echo "<br/>\n";
    if (isset($_POST['nl2br'])) {
        echo '<input type="checkbox" name="nl2br" value="choose" checked>Neue Zeile durch entsprechendes HTML ersetzen';
    } else {
        echo '<input type="checkbox" name="nl2br" value="choose">Neue Zeile durch entsprechendes HTML ersetzen';
    }
} else {
    echo '<textarea name="text" cols="100" rows="20"></textarea>';
    echo "<br/>\n";
    echo '<input type="checkbox" name="nl2br" value="choose" checked>Neue Zeile durch entsprechendes HTML ersetzen';
}
//echo "<br/>\n";
//echo '<input type="checkbox" name="nl2br" value="choose" checked>Neue Zeile durch entsprechendes HTML ersetzen';
echo '     <input type="submit" value="Show"/>';
echo '</form>';
//var_dump($_POST);
echo "<h2>Ausgabe</h2>";
if (isset($n)) {
    if (isset($_POST['nl2br'])) {
        echo "<div id=\"anzeige\">" . nl2br($n) . "</div>";
    } else {
        echo "<div id=\"anzeige\">" . $n . "</div>";
    }
}

?>
</body>
</html>
