<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
 *
 * Copyright (C) 2019 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * k10plus?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * k10plus?ppn=PPN
 *   PPN ist die eine ID-Nummer des K10PLUS
 * k10plus?isbn=ISBN&format=json
 * k10plus?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 * k10plus?swn=SWN
 *   SWN ist die alte SWB PPN
 *
 * Sucht übergebene ISBN bzw. PPN im K10PLus-Katalog
 * und gibt maximal 10 Ergebnisse als MARCXML zurück
 * bzw. als JSON.
 *
 * Info zur SRU-Schnittstelle: https://wiki.k10plus.de/display/K10PLUS/SRU
 */

include 'lib.php';

$urlBase = 'https://sru.k10plus.de/opac-de-627?version=1.1&operation=searchRetrieve&query=';
$urlSuffix = '&maximumRecords=10&recordSchema=marcxml';

if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = explode(",", $n);
    $suchString = 'pica.isb%3D' . implode('+OR+pica.isb=', $nArray);
}
if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'pica.ppn%3D' . $ppn;
}

if (isset($_GET['swn'])) {
    $ppn = trim($_GET['swn']); // alte SWB PPN
    $suchString = 'pica.swn%3D' . $ppn;
}

$result = file_get_contents($urlBase . $suchString . $urlSuffix);

if ($result === false) {
    header('HTTP/1.1 400 Bad Request');
    var_dump($urlBase . $suchString . $urlSuffix);
    exit;
}

$result = str_replace(' xmlns:zs="http://www.loc.gov/zing/srw/"', '', $result);
$result = str_replace(' xmlns="http://www.loc.gov/MARC21/slim"', '', $result);
$result = str_replace('zs:', '', $result);

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


$map = $standardMarcMap;
$map['bestand'] = '//datafield[@tag="924"]/subfield[@code="b"]';
//TODO special mapping K10PLUS, if needed?
//$map['bestand'] = '//datafield[@tag="949" or @tag="852"]/subfield[@code="F"]';
//$map['sw']['additional'] = './subfield[@code="z"]';

if (!isset($_GET['format'])) {
    header('Content-type: text/xml');
    echo $outputString;
} else if ($_GET['format'] == 'json') {
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
