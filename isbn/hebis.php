<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
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

include 'lib.php';

$id = yaz_connect(HEBIS_URL, array("user" => HEBIS_USER, "password" => HEBIS_PASSWORD));
yaz_syntax($id, HEBIS_SYNTAX);//
yaz_range($id, 1, 10);
yaz_element($id, HEBIS_ELEMENTSET); //


if (isset($_GET['ppn'])) {
    $ppn = trim($_GET['ppn']);
    yaz_search($id, "rpn", '@attr 5=100 @attr 1=12 "' . $ppn . '"');
}
if (isset($_GET['isbn'])) {
    $n = trim($_GET['isbn']);
    $nArray = explode(",", $n);
    if (count($nArray)>1) {
        //mehrere ISBNs, z.B. f @or @or @attr 1=7 "9783937219363" @attr 1=7 "9780521369107" @attr 1=7 "9780521518147"
        //Anfuehrungsstriche muessen demaskiert werden, egal ob String mit ' gemacht wird
        $suchString = str_repeat("@or ", count($nArray)-1) . '@attr 1=7 \"' . implode('\" @attr 1=7 \"', $nArray) . '\"';
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
    echo "Error Description: " . $error ;
    echo "Additional Error Information: " . yaz_addinfo($id);
}


$outputString = "<?xml version=\"1.0\"?>\n";
$outputString .= "<collection>\n";
$outputArray = [];

for ($p = 1; $p <= yaz_hits($id); $p++) {
    yaz_syntax($id, "marc21");//In der for-Schlaufe muss von Pica-Format wieder auf marc21 umgeschaltet werden
    $record = yaz_record($id, $p, "xml;charset=iso5426,utf8");//Umwandlung in XML
    $record = str_replace('xmlns="http://www.loc.gov/MARC21/slim"', '', $record);
    $recordXml = simplexml_load_string("<?xml version=\"1.0\"?>".$record);//Umwandlung in ein SimpleXMLElement Objekt

    //NACHBEARBEITUNG:
    //Nachbearbeitung noetig, da leider nicht alle Daten im MARC21 Format liegen.
    //Z.B. RVK, DDC, ...
    //Daher wird vom PICA Format ausgehend, diese Daten gesucht.
    yaz_syntax($id, "mab2");//mab2 ist Pica-Format
    $recordPica = yaz_record($id, $p, "string");//render;charset=iso5426,utf8
    $recordPica = strtr($recordPica, "\x9f", "$");//Unterfeld

    //DDC im HEBIS
    //" �eDDCoclc�a006.6" = 209f654444436f636c639f613030362e36 hex
    preg_match_all('/045[FB] .*\$a(\d{3}\.?\d*)/m', $recordPica, $description);
    for ($i = 0; $i < count($description[1]); $i++) {
        $field = $recordXml->addChild("datafield");
        $field->addAttribute('tag', '082');
        $field->addAttribute('ind1', ' ');
        $field->addAttribute('ind2', ' ');
        $subfield = $field->addChild("subfield", $description[1][$i]);
        $subfield->addAttribute('code', 'a');
    }

    //RVK im HEBIS
    //045Z $aST 151
    preg_match_all('/045Z \$a([^\<\n\|]*)/', $recordPica, $description);
    for ($i = 0; $i < count($description[1]); $i++) {
        $field = $recordXml->addChild("datafield");
        $field->addAttribute('tag', '084');
        $field->addAttribute('ind1', ' ');
        $field->addAttribute('ind2', ' ');
        $subfield = $field->addChild("subfield", $description[1][$i]);
        $subfield->addAttribute('code', 'a');
        $subfield2 = $field->addChild("subfield", 'rvk');
        $subfield2->addAttribute('code', '2');
    }

    //Schlagwörter
    //041A/09 $9125282621$VTs1$04806620-5$aAgile Softwareentwicklung
    //044K $9223456969$VTs1$07702558-1$aVisual C sharp 2010
    //041A/07 $af Kongress ==> funktioniert mit RegExp unten nicht
    //041A/08 $ag Burgas <2011> ==> funktioniert mit RegExp unten nicht
    preg_match_all('/04(4K|1A).*\$0([^\$\<\n]*).*\$a([^\$\<\n]*)/', $recordPica, $description);
    for ($i = 0; $i < count($description[2]); $i++) {
        $field = $recordXml->addChild("datafield");
        $field->addAttribute('tag', '689');
        $field->addAttribute('ind1', '0');
        $field->addAttribute('ind2', '0');
        $subfield = $field->addChild("subfield", '(DE-588)'.$description[2][$i]);
        $subfield->addAttribute('code', '0');
        $subfield2 = $field->addChild("subfield", $description[3][$i]);
        $subfield2->addAttribute('code', 'a');
    }

    //AUSGABE DES ANGEREICHTERTEN RECORD
    //erste Zeile abschneiden, da xml Deklaration bereits in der collection erledigt wurde.
    $outputString .=  str_replace('<?xml version="1.0"?>', "", $recordXml->asXML());
    array_push($outputArray, str_replace('<?xml version="1.0"?>', "", $recordXml->asXML()));
}
$outputString .=  "</collection>";
yaz_close($id);


$map = $standardMarcMap;
$map['bestand'] = '//datafield[@tag="924"]/subfield[@code="b"]';


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
