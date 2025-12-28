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
 * b3kat?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * b3kat?ppn=PPN
 *   PPN ist die eine ID-Nummer des B3KAT
 * b3kat?isbn=ISBN&format=json
 * b3kat?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN in der SRU-Schnittstelle vom B3KAT
 * und gibt maximal 10 Ergebnisse als MARCXML oder JSON zurück.
 *
 * http://bvbr.bib-bvb.de:5661/bvb01sru?version=1.1&recordSchema=marcxml&operation=explain
 */

include 'conf.php';
include 'lib.php';

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'marcxml.idn=' . $ppn;
}

$urlBase = 'http://bvbr.bib-bvb.de:5661/bvb01sru?version=1.1&maximumRecords=10&recordSchema=marcxml&operation=searchRetrieve&query=';
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = preg_split("/\s*(or|,|;)\s*/i", $n, -1, PREG_SPLIT_NO_EMPTY);
    $suchString = 'marcxml.isbn=' . implode('+OR+marcxml.isbn=', $nArray);
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
$result = str_replace(' xmlns="http://www.loc.gov/MARC21/slim"', '', $result);
$result = str_replace(' xmlns:zs="http://www.loc.gov/zing/srw/"', '', $result);
$result = str_replace('<zs:', '<', $result);
$result = str_replace('</zs:', '</', $result);

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
$map['bestand'] = '//datafield[@tag="049"]/subfield[@code="a"]';

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
