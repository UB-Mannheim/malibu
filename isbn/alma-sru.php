<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
 *
 * Copyright (C) 2024 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * alma-sru?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * alma-sru?bibliothek=BIB&isbn=ISBN&format=json
 * alma-sru?bibliothek=BIB&isbn=ISBN&format=holdings
 * alma-sru?bibliothek=BIB&isbn=ISBN&format=holdings&with=collections
*
 * Sucht übergebene ISBN bzw. PPN in der SRU-Schnittstelle einer Alma-Bibliothek
 * und gibt maximal 10 Ergebnisse als MARCXML, JSON zurück oder eine
 * formattierte Bestandsangabe (eine kurze Zeile und die Details in einer
 * Tabelle).
 */

include 'conf.php';
include 'lib.php';

$file = file_get_contents('./srulibraries.json');
$json = json_decode($file, true);
if (isset($_GET['bibliothek']) and isset($json[$_GET['bibliothek']])) {
    $urlBase = $json[$_GET['bibliothek']]['sru'];
} else {
    echo "Bibliothek nicht gefunden in der Liste der bekannten Alma-SRU-Schnittstellen.\n";
    exit;
}

$urlBase = $urlBase . '?version=1.2&operation=searchRetrieve&recordSchema=marcxml&query=';

if (!isset($_GET['ppn']) and !isset($_GET['isbn'])) {
    echo "Weder isbn noch ppn Parameter für eine Suche angegeben.\n";
    exit;
}

if (isset($_GET['ppn'])) {
    $n = trim($_GET['ppn']);
    $searchObject = "ppn";
}
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $searchObject = "isbn";
}
$nArray = preg_split("/\s*(or|,|;)\s*/i", $n, -1, PREG_SPLIT_NO_EMPTY);
for ($i = 0; $i < count($nArray); $i++) {
    $nArray[$i] = str_replace("-", "", $nArray[$i]);
}
$suchString = 'alma.all=' . implode('+OR+alma.all=', $nArray);
$filteredSuchString = 'alma.mms_tagSuppressed=false' . '+AND+(' . $suchString . ')';

# work around ExLibris server configuration issue
# and increase timeout (i.e. waiting time)
$contextOptions = [
    'ssl' => [
        'verify_peer' => true,
        'ciphers' => 'DEFAULT@SECLEVEL=1',
    ],
    'http' => [
        'timeout' => 10,
    ],
];
$context = stream_context_create($contextOptions);
$result = @file_get_contents($urlBase . $filteredSuchString, false, $context);

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
    // or the PPN in the 001 or 035 field(s).
    $pattern = [
        "isbn" => './/datafield[@tag="020" or @tag="776"]/subfield',
        "ppn" => './/controlfield[@tag="001"]|.//datafield[@tag="035"]/subfield'
    ];
    $foundMatch = false;
    $nodes = $xpath->query($pattern[$searchObject], $record);
    foreach ($nodes as $foundNode) {
        $foundValue = $foundNode->nodeValue;
        foreach ($nArray as $queryValue) {
            $testString = $queryValue;
            if ($searchObject == "isbn") {
                $testString = preg_replace('/[^0-9xX]/', '', $testString);
                $foundValue = preg_replace('/[^0-9xX]/', '', $foundValue);
                if (strlen($testString) == 13) {
                    // Delete the 978-prefix and the check value at the end for ISBN13
                    $testString = substr($testString, 3, -1);
                } elseif (strlen($testString) == 10) {
                    // Delete check value at the end for ISBN10
                    $testString = substr($testString, 0, -1);
                }
                // for isbn, check that the test string is part of the found value
                if (strpos($foundValue, $testString) !== false) {
                    $foundMatch = true;
                }
            } else {
                // for ppn (or other ids), skip the possible prefix in paranthesis and then they need to be exactly the same
                $foundValue = preg_replace('/^\(.*\)/', '', $foundValue);
                if ($foundValue == $testString) {
                    $foundMatch = true;
                }
            }
        }
    }
    if ($foundMatch) {
        $outputString .= $doc->saveXML($record);
        array_push($outputArray, $doc->saveXML($record));
    }
}
$outputString .= "</collection>";


$map = STANDARD_MARC_MAP;
$map['bestand'] = '//datafield[@tag="AVA"]/subfield[@code="b"]';
$map['sammlung'] = '//datafield[@tag="AVE"]/subfield[@code="m"]';
// TODO Prüfen ob man die SW nicht allgemeingültig so wie folgt behandeln könnte
// (Feld 689 wird von HBZ und SWISS genutzt und Feld 650 von SWISS;
// Unterfeld 2 hat nur SWISS mit "gnd" gefüllt; aber alle nutzen Unterfeld
// 0 zur Verlinkung mit der GND beginnend mit "(DE-588)". Aber unklar wie dies
// etwa bei Formschlagwörtern ohne Verlinkung aussieht.)
$map['sw'] = array(
    'mainPart' => '//datafield[(starts-with(@tag,"6") and (starts-with(subfield[@code="0"],"(DE-588)") or subfield[@code="2"]="gnd")) or (@tag="655" and @ind2="7" and subfield[@code="2"]="gnd-content")]',
        'value' => './subfield[@code="a"]',
        'subvalues' => './subfield[@code="b" or @code="t"]',
        'additional' => './subfield[@code="g" or @code="z"]',
        'key' => './subfield[@code="0" and contains(text(), "(DE-588)")]'
    );

if (!isset($_GET['format'])) {
    header('Content-type: text/xml');
    echo $outputString;
} elseif ($_GET['format'] == 'json') {
    $outputXml = simplexml_load_string($outputString);

    $outputMap = performMapping($map, $outputXml);

    $outputIndividualMap = [];
    for ($j = 0; $j < count($outputArray); $j++) {
        $singleRecordXml = simplexml_load_string($outputArray[$j]);
        $outputSingleMap = performMapping($map, $singleRecordXml);
        array_push($outputIndividualMap, $outputSingleMap);
    }
    $outputMap["einzelaufnahmen"] = $outputIndividualMap;


    header('Content-type: application/json');
    echo json_encode($outputMap, JSON_PRETTY_PRINT);
} elseif ($_GET['format'] == 'holdings') {
    echo "<html>\n<head>\n    <title>Bestand Alma-SRU zu ISBN-Suche</title>\n    <meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n    <style type='text/css'>body { font-family:  Arial, Verdana, sans-serif; }</style>\n</head>\n<body>\n";
    $outputXml = simplexml_load_string($outputString);
    $avaNodes = $outputXml->xpath('//datafield[@tag="AVA"]');
    $aveNodes = $outputXml->xpath('//datafield[@tag="AVE"]');
    $size = strlen($outputString);
    if ($avaNodes) {
        echo "<table>\n";
        $bestand = [];
        foreach ($avaNodes as $node) {
            echo "<tr>\n";
            $subfields = $node->xpath('./subfield');
            foreach ($subfields as $subfield) {
                $code = $subfield[0]["code"];
                $value = getValues($subfield[0]);
                echo "   <td>" . $value . "</td>";
            }
            echo "\n</tr>\n";

            $location = getValues($node->xpath('./subfield[@code="b"]')[0]);
            $sublocation = getValues($node->xpath('./subfield[@code="c"]')[0]);

            $node_f = $node->xpath('./subfield[@code="f"]');
            $number = count($node_f) ? getValues($node_f[0]) : 0;
            if (array_key_exists($location, $bestand)) {
                $bestand[$location] += $number;
            } else {
                $bestand[$location] = $number;
            }
        }
        echo "</table>\n";
        echo "<hr/>\n";
        if ($aveNodes) {
            $collections = [];
            echo "<table>\n";
            foreach ($aveNodes as $node) {
                echo "<tr>\n";
                $subfields = $node->xpath('./subfield');
                foreach ($subfields as $subfield) {
                    $code = $subfield[0]["code"];
                    $value = getValues($subfield[0]);
                    echo "   <td>" . $value . "</td>";
                }
                echo "\n</tr>\n";
                $collection = $node->xpath('./subfield[@code="m"]');
                if ($collection) {
                    $collectionValue = getValues($collection[0]);
                    $availability = $node->xpath('./subfield[@code="e"]');
                    if ($availability and getValues($availability[0]) != "Available") {
                        $collectionValue .= " [" . getValues($availability[0]) . "]";
                    }
                    $collections[] = $collectionValue;
                }
            }
            echo "</table>\n";
            echo "<hr/>\n";
        }


        echo '<div>Bestand Alma-SRU: ';
        foreach ($bestand as $loc => $n) {
            echo $n . "x" . $loc . ", ";
        }
        if ($aveNodes) {
            echo "E";
            if (isset($_GET['with']) && $_GET['with']=="collections") {
                sort($collections, SORT_STRING | SORT_FLAG_CASE);
                echo ' (' . implode(" | ", $collections) . ')';
            }
        }
        echo '</div>';
    } elseif ($aveNodes and !$avaNodes) {
        echo "<table>\n";
        $collections = [];
        foreach ($aveNodes as $node) {
            echo "<tr>\n";
            $subfields = $node->xpath('./subfield');
            foreach ($subfields as $subfield) {
                $code = $subfield[0]["code"];
                $value = getValues($subfield[0]);
                echo "   <td>" . $value . "</td>";
            }
            echo "\n</tr>\n";
            $collection = $node->xpath('./subfield[@code="m"]');
            if ($collection) {
                $collectionValue = getValues($collection[0]);
                $availability = $node->xpath('./subfield[@code="e"]');
                if ($availability and getValues($availability[0]) != "Available") {
                    $collectionValue .= " [" . getValues($availability[0]) . "]";
                }
                $collections[] = $collectionValue;
            }
        }
        echo "</table>\n";
        echo "<hr/>\n";
        echo '<div>Bestand Alma-SRU: E';
        if (isset($_GET['with']) && $_GET['with']=="collections") {
            sort($collections, SORT_STRING | SORT_FLAG_CASE);
            echo ' (' . implode(" | ", $collections) . ')';
        }
        echo '</div>';
    } elseif ($size > 100) {
        //if the isbn/ppn is not found, then the $outputString is a minimal xml document
        //of size 48, for larger size something might be found...
        $sruUrl = str_replace('format=holdings', '', $_SERVER['REQUEST_URI']);
        echo '<div>Bestand Alma-SRU: eventuell da (' . $size . ")</div>\n";
        echo '<table><tr><td><a href="' . $sruUrl . '" taget="_blank">See SRU Result</a></td></tr></table>';
    } else {
        echo 'Es wurde nichts gefunden';
    }
    echo "\n</body>\n</html>";
}
