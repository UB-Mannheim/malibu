<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
 *
 * Copyright (C) 2013 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * Aufruf aus Webbrowser:
 * bl?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * bl?ppn=PPN
 *   PPN ist die eine ID-Nummer des BL
 * bl?isbn=ISBN&format=json
 * bl?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im BL-Katalog
 * und gibt maximal 10 Ergebnisse als MARCXML zurück
 * bzw. als JSON.
 */

include 'conf.php';
include 'lib.php';

$id = yaz_connect(BL_URL, array("user" => BL_USER, "password" => BL_PASSWORD));
yaz_syntax($id, BL_SYNTAX); //utf-8 Kodierung
yaz_range($id, 1, 10);
yaz_element($id, BL_ELEMENTSET);

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    yaz_search($id, "rpn", '@attr 5=100 @attr 1=12 "' . $ppn . '"');
}
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = explode(",", $n);
    if (count($nArray) > 1) {
        //mehrere ISBNs, z.B. f @or @or @attr 1=7 "9783937219363" @attr 1=7 "9780521369107" @attr 1=7 "9780521518147"
        //Anfuehrungsstriche muessen demaskiert werden, egal ob String mit ' gemacht wird
        $suchString = str_repeat("@or ", count($nArray) - 1) . '@attr 1=7 \"' . implode('\" @attr 1=7 \"', $nArray) . '\"';
        yaz_search($id, "rpn", $suchString);
    } else {
        yaz_search($id, "rpn", '@attr 5=100 @attr 1=7 "' . $n . '"');
    }
    // @attr 5=100 -> no truncation, ist aber Standardeinstellung, kann daher auch weg
}
yaz_wait();
$error = yaz_error($id);
if (!empty($error)) {
    echo "Error Number: " . yaz_errno($id);
    echo "Error Description: " . $error;
    echo "Additional Error Information: " . yaz_addinfo($id);
}

$outputString = "<?xml version=\"1.0\"?>\n";
$outputString .= "<collection>\n";
$outputArray = [];

for ($p = 1; $p <= yaz_hits($id); $p++) {
    $record = yaz_record($id, $p, "xml");
    //namespace löschen
    $record = str_replace('xmlns="http://www.loc.gov/MARC21/slim"', '', $record);
    $outputString .= $record;
    array_push($outputArray, $record);
}
$outputString .= "</collection>";

yaz_close($id);

$map = STANDARD_MARC_MAP;
$map['bestand'] = '//datafield[@tag="900"]/subfield[@code="b"]';


if (!isset($_GET['format'])) {
    header('Content-type: text/xml');
    echo $outputString;
} elseif ($_GET['format'] == 'json') {
    $outputXml = simplexml_load_string($outputString);
    $outputMap = performMapping($map, $outputXml);
    $outputIndividualMap = [];
    for ($j = 0; $j < count($outputArray); $j++) {
        $outputXml = simplexml_load_string($outputArray[$j]);
        $outputSingleMap = performMapping($map, $outputXml);
        array_push($outputIndividualMap, $outputSingleMap);
    }
    $outputMap["einzelaufnahmen"] = $outputIndividualMap;


    header('Content-type: application/json');
    echo json_encode($outputMap, JSON_PRETTY_PRINT);
}
