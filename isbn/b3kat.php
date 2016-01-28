<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
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
 * Aufruf aus Webbrowser:
 * b3kat?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * b3kat?ppn=PPN
 *   PPN ist die eine ID-Nummer des B3KAT
 * b3kat?isbn=ISBN&format=json
 * b3kat?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im B3KAT-Katalog
 * und gibt maximal 10 Ergebnisse als MABXML zurück
 * bzw. als JSON.
 */

include 'lib.php';

$id = yaz_connect(B3KAT_URL, array("user" => B3KAT_USER, "password" => B3KAT_PASSWORD));//"mab2; charset=iso5426,utf8"
yaz_syntax($id, B3KAT_SYNTAX);
yaz_range($id, 1, 10);
yaz_element($id, B3KAT_ELEMENTSET);

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    //aus der Doku: 53 Number local call (BV-Nr.)
    yaz_search($id, "rpn", '@attr 5=100 @attr 1=53 "' . $ppn . '"');
}
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = explode(",", $n);
    if (count($nArray)>1) {
        //mehrere ISBNs, z.B. f @or @or @attr 1=7 "9783937219363" @attr 1=7 "9780521369107" @attr 1=7 "9780521518147"
        //Anfuehrungsstriche muessen demaskiert werden, egal ob String mit ' gemacht wird
        $suchString = str_repeat("@or ", count($nArray)-1) . '@attr 1=7 \"' . implode('\" @attr 1=7 \"', $nArray) . '\"';
        yaz_search($id, "rpn", $suchString);
    } else {
        yaz_search($id, "rpn", '@attr 5=100 @attr 1=7 "' . $n . '"');
    }
    // @attr 5=100 -> no truncation, ist aber Standardeinstellung, kann daher auch weg
}


yaz_wait();
$error = yaz_error($id);
if (!empty($error)) {
    echo "Error Number: " + yaz_errno($id);
    echo "Error Description: " + $error ;
    echo "Additional Error Information: " + yaz_addinfo($id);
}

$outputString = "<?xml version=\"1.0\"?>\n";
$outputString .= "<datei>\n";
$outputArray = [];


for ($p = 1; $p <= yaz_hits($id); $p++) {
    $record = yaz_record($id, $p, "render;charset=iso5426,utf8");//render;charset=iso5426,utf8
    $recordArray =  explode("\x1e", $record);
    $header = substr($recordArray[0], 0, 24);
    $recordContent = '<datensatz id="" typ="'.substr($header, 23, 1).'" status="'.substr($header, 5, 1).'" mabVersion="'.substr($header, 6, 4).'">'."\n";
    $recordContent .= printLine(substr($recordArray[0], 24));

    for ($j = 1; $j < count($recordArray); $j++) {
        $recordContent .= printLine($recordArray[$j]);
    }

    $recordContent .=  '</datensatz>'."\n";
    $outputString .=  $recordContent;
    array_push($outputArray, $recordContent);

}

$outputString .=  "</datei>";
yaz_close($id);

$map = $standardMabMap;
$map['bestand'] = '//feld[@nr="LOW" and @ind="a"]';

if (!isset($_GET['format'])) {
    header('Content-type: text/xml');
    echo $outputString;

} else if ($_GET['format']=='json') {

    $outputXml = simplexml_load_string($outputString);
    $outputMap = performMapping($map, $outputXml);
    $outputIndividualMap = [];
    for ($j=0; $j<count($outputArray); $j++) {
        $outputXml = simplexml_load_string($outputArray[$j]);
        $outputSingleMap = performMapping($map, $outputXml);
        array_push($outputIndividualMap, $outputSingleMap);
    }
    $outputMap["einzelaufnahmen"] = $outputIndividualMap;


    header('Content-type: application/json');
    echo json_encode($outputMap, JSON_PRETTY_PRINT);
}

?>
