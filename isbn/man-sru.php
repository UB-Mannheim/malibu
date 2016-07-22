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

https://eu01.alma.exlibrisgroup.com/view/sru/49MAN_INST?version=1.2&operation=explain
*/

$urlBase = 'https://eu01.alma.exlibrisgroup.com/view/sru/49MAN_INST?version=1.2&operation=searchRetrieve&recordSchema=marcxml&query=';
//$urlSuffix = '+sortBy+alma.title/sort.descending';

if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = preg_split("/\s*(or|,)\s*/i", $n);
    $suchString = 'alma.all=' . implode('+OR+alma.all=', $nArray);
    $suchStringSWB = implode(' or ', $nArray);
}
$result = file_get_contents($urlBase . $suchString);

if ($result === false) {
    header('HTTP/1.1 400 Bad Request');
    echo "Verbindung zu SRU-Schnittstelle fehlgeschlagen\n";
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
$map['bestand'] = '//datafield[@tag="AVA"]/subfield[@code="b"]';

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
} else if ($_GET['format']=='holdings') {
    echo "<html>\n<head>\n	<title>Bestand UB Mannheim zu ISBN-Suche</title>\n	<meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n	<style type='text/css'>body { font-family:  Arial, Verdana, sans-serif; }</style>\n</head>\n<body>\n";
    $outputXml = simplexml_load_string($outputString);
    $avaNodes = $outputXml->xpath('//datafield[@tag="AVA"]');
    $aveNodes = $outputXml->xpath('//datafield[@tag="AVE"]');
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
            echo $n . "x" . $loc .  ", ";
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
    } else {
        $urlSWB='http://swb.bsz-bw.de/DB=2.1/SET=11/TTL=2/CMD?ACT=SRCHM&ACT0=SRCH&IKT0=2135&TRM0=%60180%60&ACT1=*&IKT1=1016&TRM1=' . $suchStringSWB;
        $contentSWB = utf8_decode(file_get_contents($urlSWB));
        //echo $contentSWB;
        if (strpos($contentSWB, "Es wurde nichts gefunden")===false) {
            $nhits = substr_count($contentSWB, 'class="hit"');
            echo '<div>Holdings in UB Mannheim: SWB sagt ja (<a href="' . $urlSWB . '" target="_blank">' . $nhits/2 .' hits</a>)';
        } else {
            echo 'Es wurde nichts gefunden';
        }
    }
    echo "\n</body>\n</html>";
}
