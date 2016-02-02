<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
 *
 * Copyright (C) 2015 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * swissbib?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * swissbib?ppn=PPN
 *   PPN ist die eine ID-Nummer des swissbib
 * swissbib?isbn=ISBN&format=json
 * swissbib?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im swissbib-Katalog
 * und gibt maximal 10 Ergebnisse als MARCXML zurück
 * bzw. als JSON.
 */

include 'lib.php';

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'dc.id=' . $ppn;
}

/*
http://sru.swissbib.ch/sru/search/defaultdb?query=
+dc.identifier+%3D+978981-4447508+OR+dc.identifier+%3D+981444751
&operation=searchRetrieve&recordSchema=info%3Asrw%2Fschema%2F1%2Fmarcxml-v1.1-light&maximumRecords=10&x-info-10-get-holdings=true&startRecord=0&recordPacking=XML&availableDBs=defaultdb&sortKeys=Submit+query
*/

$urlBase = 'http://sru.swissbib.ch/sru/search/defaultdb?query=';
$urlSuffix = '&operation=searchRetrieve&recordSchema=info%3Asrw%2Fschema%2F1%2Fmarcxml-v1.1-light&maximumRecords=10&x-info-10-get-holdings=true&startRecord=0&recordPacking=XML&availableDBs=defaultdb&sortKeys=Submit+query';

if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = explode(",", $n);
    $suchString = 'dc.identifier=' . implode('+OR+dc.identifier=', $nArray);
}

$result = file_get_contents($urlBase . $suchString . $urlSuffix);

if ($result === false) {
    var_dump($urlBase . $suchString . $urlSuffix);
    exit;
}

$result = str_replace(' xmlns:xs="http://www.w3.org/2001/XMLSchema"', '', $result);

$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
@$doc->loadHTML($result);
$xpath = new DOMXPath($doc);

$records = $xpath->query("//records/record/recorddata/record");//beachte: kein CamelCase sondern alles klein schreiben

$outputString = "<?xml version=\"1.0\"?>\n";
$outputString .= "<collection>\n";
$outputArray = [];

foreach ($records as $record) {

    $outputString .=  $doc->saveXML($record);
    array_push($outputArray, $doc->saveXML($record));
}
$outputString .=  "</collection>";


$map = $standardMarcMap;


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
