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
 * hebis?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * hebis?ppn=PPN
 *   PPN ist die eine ID-Nummer des SWB
 * hebis?isbn=ISBN&format=json
 * hebis?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im HEBIS-Katalog
 * und gibt maximal 10 Ergebnisse als MARCXML zurück
 * bzw. als JSON.
 */

include 'conf.php';
include 'lib.php';

$id = yaz_connect(HEBIS_URL);
yaz_syntax($id, HEBIS_SYNTAX); //
yaz_range($id, 1, 10);
yaz_element($id, HEBIS_ELEMENTSET); //

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
    $record = yaz_record($id, $p, "xml"); //Umwandlung in XML
    if (!strlen($record)) {
        error_log("Empty record in " . __FILE__ . ", line " . __LINE__);
    }
    //namespace löschen
    $record = str_replace('xmlns="http://www.loc.gov/MARC21/slim"', '', $record);
    $outputString .= $record;
    array_push($outputArray, $record);
}
$outputString .= "</collection>";
yaz_close($id);

$map = STANDARD_MARC_MAP;
$map['bestand'] = '//datafield[@tag="924"]/subfield[@code="b"]';


if (!isset($_GET['format'])) {
    header('Content-type: text/xml');
    echo $outputString;
} elseif ($_GET['format'] == 'json') {
    $outputXml = simplexml_load_string($outputString);
    $outputMap = performMapping($map, $outputXml);
    $outputIndividualMap = [];
    for ($j = 0; $j < count($outputArray); $j++) {
        if (!strlen($outputArray[$j])) {
            continue;
        }
        $outputXml = simplexml_load_string($outputArray[$j]);
        if ($outputXml === false) {
            error_log("Error loading xml in " . __FILE__ . ", line " . __LINE__ . " from: "
                      . print_r($outputArray[$j], true));
            continue;
        }
        $outputSingleMap = performMapping($map, $outputXml);
        array_push($outputIndividualMap, $outputSingleMap);
    }
    $outputMap["einzelaufnahmen"] = $outputIndividualMap;


    header('Content-type: application/json');
    echo json_encode($outputMap, JSON_PRETTY_PRINT);
}
