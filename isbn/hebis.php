<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
 *
 * Copyright (C) 2025 Universitätsbibliothek Mannheim
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
 * 
 * http://sru.hebis.de/sru/DB=2.1?version=1.1&recordSchema=marcxml&operation=explain
 */

include 'conf.php';
include 'lib.php';

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'pica.ppn%3D' . $ppn;
}

$urlBase = 'http://sru.hebis.de/sru/DB=2.1?version=1.1&maximumRecords=10&recordSchema=marcxml&operation=searchRetrieve&query=';
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = preg_split("/\s*(or|,|;)\s*/i", $n, -1, PREG_SPLIT_NO_EMPTY);
    $suchString = 'pica.isb%3D' . implode('+OR+pica.isb%3D', $nArray);
}

$result = @file_get_contents($urlBase . $suchString, false);

if ($result === false) {
    header('HTTP/1.1 400 Bad Request');
    echo "Verbindung zu SRU-Schnittstelle fehlgeschlagen\n";
    var_dump($urlBase . $suchString);
    exit;
}

// Delete namespaces such that we don't need to specify them
// in every xpath query.
$result = str_replace(' xmlns:="http://www.loc.gov/zing/srw/"', '', $result);
$result = str_replace('<srw:', '<', $result);
$result = str_replace('</srw:', '</', $result);
$result = str_replace(' xmlns:dc="http://purl.org/dc/elements/1.1/"', '', $result);
$result = str_replace(' xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/"', '', $result);
$result = str_replace(' xmlns:xcql="http://www.loc.gov/zing/cql/xcql/"', '', $result);

$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
@$doc->loadHTML($result);
$xpath = new DOMXPath($doc);

$records = $xpath->query("//records/record/recorddata/record"); //beachte: kein CamelCase sondern alles klein schreiben

$outputString = "<?xml version=\"1.0\"?>\n";
$outputString .= "<collection>\n";
$outputArray = [];

foreach ($records as $record) {
    $outputString .= $doc->saveXML($record);
    array_push($outputArray, $doc->saveXML($record));
}
$outputString .= "</collection>";

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
        $outputXml = simplexml_load_string($outputArray[$j]);
        $outputSingleMap = performMapping($map, $outputXml);
        array_push($outputIndividualMap, $outputSingleMap);
    }
    $outputMap["einzelaufnahmen"] = $outputIndividualMap;


    header('Content-type: application/json');
    echo json_encode($outputMap, JSON_PRETTY_PRINT);
}
