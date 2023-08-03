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
 * Aufruf aus Webbrowser:
 * hbz?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * hbz?ppn=PPN
 *   PPN ist die eine ID-Nummer des HBZ
 * hbz?isbn=ISBN&format=json
 * hbz?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im HBZ-Katalog
 * und gibt maximal 10 Ergebnisse als MABXML zurück
 * bzw. als JSON.
 */

include 'conf.php';
include 'lib.php';

$id = yaz_connect(HBZ_URL);
yaz_syntax($id, HBZ_SYNTAX);

yaz_range($id, 1, 10);
yaz_element($id, HBZ_ELEMENTSET);

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
        $suchString = str_repeat("@or ", count($nArray) - 1) . '@attr 1=7 \"'
                    . implode('\" @attr 1=7 \"', $nArray) . '\"';
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
$outputString .= "<datei>\n";
$outputArray = [];

for ($p = 1; $p <= yaz_hits($id); $p++) {
    $record = yaz_record($id, $p, "render;charset=iso5426,utf8"); //render;charset=iso5426,utf8
    $error = yaz_error($id);
    if (!empty($error)) {
        echo "Error Number: " . yaz_errno($id);
        echo "Error Description: " . $error;
        echo "Additional Error Information: " . yaz_addinfo($id);
    }
    $recordArray = explode("\x1e", $record);
    $header = substr($recordArray[0], 0, 24);
    $recordContent = '<datensatz id="" typ="' . substr($header, 23, 1) . '" status="'
                   . substr($header, 5, 1) . '" mabVersion="'
                   . substr($header, 6, 4) . '">' . "\n";
    $recordContent .= printLine(substr($recordArray[0], 24));


    for ($j = 1; $j < count($recordArray); $j++) {
        $recordContent .= printLine($recordArray[$j]);
    }

    $recordContent .= '</datensatz>' . "\n";
    $outputString .= $recordContent;
    array_push($outputArray, $recordContent);
}

$outputString .= "</datei>";
yaz_close($id);

$map = STANDARD_MAB_MAP;

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
