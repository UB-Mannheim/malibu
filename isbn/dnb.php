<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
 *
 * Copyright (C) 2025 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * dnb.php?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * dnb.php?isbn=ISBN&format=json
 *
 * Sucht übergebene ISBN bzw. PPN in der SRU-Schnittstelle der DNB
 * und gibt maximal 10 Ergebnisse als MARCXML oder JSON zurück.
 */
 
 /*
Explain SRU

https://services.dnb.de/sru/dnb?version=1.1&operation=explain

(v1.2 not supported, Bestandsdaten im Format MARC21plus-xml aber Mehraufwand und daher mal weggelassen)

https://www.dnb.de/DE/Professionell/Metadatendienste/Datenbezug/SRU/sru_node.html

*/

include 'conf.php';
include 'lib.php';

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'dnb.idn=' . $ppn;
}

$urlBase = 'https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&recordSchema=MARC21-xml&query=';
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = preg_split("/\s*(or|,|;)\s*/i", $n, -1, PREG_SPLIT_NO_EMPTY);
    $suchString = 'dnb.num=' . implode('+OR+dnb.num=', $nArray);
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
$result = str_replace(' xmlns:xs="http://www.w3.org/2001/XMLSchema"', '', $result);
$result = str_replace(' xmlns="http://www.loc.gov/MARC21/slim"', '', $result);

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
// intellektuell vergebene Schlagwörter sind immer im Feld 689 und nur falls es dort keine gibt
// sollen die anderen Felder (mit automatisch vergebenen) Schlagwörter auch betrachtet werden
$map['sw']['mainPart'] = '//datafield[@tag="689" or (not(preceding-sibling::datafield[@tag="689"]) and not(following-sibling::datafield[@tag="689"]) and starts-with(@tag, "6") and (subfield[@code="2"]="gbv" or subfield[@code="2"]="gnd"))]';


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
