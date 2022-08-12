<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
 *
 * Copyright (C) 2022 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * obvsg.php?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * obvsg.php?isbn=ISBN&format=json
 *
 * Sucht übergebene ISBN bzw. PPN in der SRU-Schnittstelle von obvsg.at (OBV-LIT)
 * und gibt maximal 10 Ergebnisse als MARCXML oder JSON zurück.
 */

include 'lib.php';

/*
SRU access

https://www.obvsg.at/services/verbundsystem/sru
*/
$urlBase = 'https://services.obvsg.at/sru/OBV-LIT?operation=searchRetrieve&query=';

$searchISBN = false;
$searchPPN = false;

if (isset($_GET['ppn'])) {
    $n = trim($_GET['ppn']);
    $searchPPN = true;
}
if (!$searchPPN && isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $searchISBN = true;
}
if ($searchPPN || $searchISBN) {
    $nArray = preg_split("/\s*(or|,|;)\s*/i", $n, -1, PREG_SPLIT_NO_EMPTY);
    $suchString = 'alma.all=' . implode('+OR+alma.all=', $nArray);
}

$filteredSuchString = 'alma.mms_tagSuppressed=false+AND+(' . $suchString . ')&maximumRecords=10';

$contextOptions = [
    'http' => [
	    'header' => 'Connection: close\r\n',
	    'timeout' => 3,
    ],
];
$context = stream_context_create($contextOptions);
$result = file_get_contents($urlBase . $filteredSuchString, false, $context);

if ($result === false) {
    header('HTTP/1.1 400 Bad Request');
    echo "Verbindung zu SRU-Schnittstelle fehlgeschlagen\n";
    var_dump($urlBase . $filteredSuchString);
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
    // Filter out any other results which contain the ISBN but not in the 020 or 776 field
    $foundMatch = false;
    if ($searchISBN) {
        $foundIsbns = $xpath->query('.//datafield[@tag="020" or @tag="776"]/subfield', $record);
        foreach ($foundIsbns as $foundNode) {
            $foundValue = $foundNode->nodeValue;
            foreach ($nArray as $queryValue) {
                $testString = preg_replace('/[^0-9xX]/', '', $queryValue);
                if (strlen($testString) == 13) {
                    // Delete the 978-prefix and the check value at the end for ISBN13
                    $testString = substr($testString, 3, -1);
                } elseif (strlen($testString) == 10) {
                    // Delete check value at the end for ISBN10
                    $testString = substr($testString, 0, -1);
                }
                if (strpos(preg_replace('[^0-9xX]', '', $foundValue), $testString) !== false) {
                    $foundMatch = true;
                }
            }
        }
    }
    if (!$searchISBN || $foundMatch) {
        $outputString .= $doc->saveXML($record);
        array_push($outputArray, $doc->saveXML($record));
    }
}
$outputString .= "</collection>";

$map = $standardMarcMap;
$map['sw'] = array(
        'mainPart' => '//datafield[starts-with(@tag,"689")]',
        'value' => './subfield[@code="a"]',
        'key' => './subfield[@code="0" and contains(text(), "(DE-588)")]'
    );

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
