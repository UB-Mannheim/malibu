<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
 *
 * Copyright (C) 2016 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf aus Webbrowser:
 * man-sru?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * man-sru?isbn=ISBN&format=json
 * man-sru?isbn=ISBN&format=holdings
 *
 * Sucht übergebene ISBN bzw. PPN in der SRU-Schnittstelle der UB Mannheim
 * und gibt maximal 10 Ergebnisse als MARCXML, JSON zurück oder eine 
 * formattierte Bestandsangabe (eine kurze Zeile und die Details in einer
 * Tabelle).
 */

include 'lib.php';

if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    $suchString = 'dc.id=' . $ppn;
}

/*
Explain SRU

https://uni-mannheim.alma.exlibrisgroup.com/view/sru/49MAN_INST?version=1.2&operation=explain
*/

$urlBase = 'https://uni-mannheim.alma.exlibrisgroup.com/view/sru/49MAN_INST?version=1.2&operation=searchRetrieve&recordSchema=marcxml&query=';

if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = preg_split("/\s*(or|,|;)\s*/i", $n);
    $suchString = 'alma.all=' . implode('+OR+alma.all=', $nArray);
    $suchStringSWB = implode(' or ', $nArray);
}
$filteredSuchString = 'alma.mms_tagSuppressed=false+AND+(' . $suchString . ')';
# work around ExLibris server configuration issue
$contextOptions = [
    'ssl' => [
        'verify_peer' => true,
        'ciphers' => 'DEFAULT@SECLEVEL=1',
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
    // Filter out any other results which contain the ISBN but not in the 020 field
    $foundMatch = false;
    $foundIsbns = $xpath->query('.//datafield[@tag="020"]/subfield', $record);
    foreach ($foundIsbns as $foundNode) {
        $foundValue = $foundNode->nodeValue;
        foreach ($nArray as $queryValue) {
            $testString = str_replace("-", "", $queryValue);
            if (strlen($testString) == 13) {
                // Delete the 978-prefix and the check value at the end for ISBN13
                $testString = substr($testString, 3, -1);
            }
            if (strpos(str_replace("-", "", $foundValue), $testString) !== false) {
                $foundMatch = true;
            }
        }
    }
    if ($foundMatch) {
        $outputString .= $doc->saveXML($record);
        array_push($outputArray, $doc->saveXML($record));
    }
}
$outputString .= "</collection>";


$map = $standardMarcMap;
$map['bestand'] = '//datafield[@tag="AVA"]/subfield[@code="b"]';

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
} else if ($_GET['format'] == 'holdings') {
    echo "<html>\n<head>\n	<title>Bestand UB Mannheim zu ISBN-Suche</title>\n	<meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n	<style type='text/css'>body { font-family:  Arial, Verdana, sans-serif; }</style>\n</head>\n<body>\n";
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
            if (strpos($sublocation, "Lehrbuchsammlung") !== false) {
                $location = "LBS";
            }
            $number = getValues($node->xpath('./subfield[@code="f"]')[0]);
            if (array_key_exists($location, $bestand)) {
                $bestand[$location] += $number;
            } else {
                $bestand[$location] = $number;
            }
        }
        echo "</table>\n";
        echo "<hr/>\n";
        if ($aveNodes) {
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
            }
            echo "</table>\n";
            echo "<hr/>\n";
        }


        echo '<div>Bestand der UB Mannheim: ';
        foreach ($bestand as $loc => $n) {
            echo $n . "x" . $loc . ", ";
        }
        if ($aveNodes) {
            echo "E";
        }
        echo '</div>';
    } else if ($aveNodes and !$avaNodes) {
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
        }
        echo "</table>\n";
        echo "<hr/>\n";
        echo '<div>Bestand der UB Mannheim: E</div>';
    } else if ($size > 100) {
        //if the isbn is not found, then the $outputString is a minimal xml document
        //of size 48, for larger size something might be found...
        $urlMAN = 'man-sru.php?isbn=' . $suchStringSWB;
        echo '<div>Bestand der UB Mannheim: eventuell da (' . $size . ")</div>\n";
        echo '<table><tr><td><a href="' . $urlMAN . '" taget="_blank">See SRU Result</a></td></tr></table>';
    } else {
        $urlSWB = 'http://swb.bsz-bw.de/DB=2.1/SET=11/TTL=2/CMD?ACT=SRCHM&ACT0=SRCH&IKT0=2135&TRM0=%60180%60&ACT1=*&IKT1=1016&TRM1=' . str_replace(" ", "+", $suchStringSWB);
        $contentSWB = utf8_decode(file_get_contents($urlSWB));
        //echo $contentSWB;
        $nhits = substr_count($contentSWB, 'class="hit"');
        $ncoins = substr_count($contentSWB, 'class="Z3988"');
        if ($nhits > 0) {//multiple results
            echo '<div>Bestand der UB Mannheim: SWB sagt ja (<a href="' . $urlSWB . '" target="_blank">' . $nhits / 2 . ' Treffer</a>)</div>';
        } else if ($ncoins > 0) {//single result
            echo '<div>Bestand der UB Mannheim: SWB sagt ja (<a href="' . $urlSWB . '" target="_blank">Einzeltreffer mit ' . $ncoins . ' COinS</a>)</div>';
        } else {
            echo 'Es wurde nichts gefunden';
        }
    }
    echo "\n</body>\n</html>";
}
