<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/bnb
 *
 * Copyright (C) 2013 Universitaetsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * GET-Parameter
 *    @nr       : Komma-separierte Liste von BNB weekly Nummern
 *    @ddc      : Komma-separierte Liste von DDC-Stellen
 *    @forth    : yes/no/only
 *    @noisbn   : yes/no/only
 *
 * Entsprechende Datensaetze werden von den XML-Dateien
 * anhand der Einstellungen gefiltert ausgegeben
 * (die Darstellung und Sortierung übernimmt aber die bnb.xsl).
 */

header('Content-type: text/xml');

if (isset($_GET['nr'])) {

    if (is_string($_GET['nr'])) {
        $nrArray = explode(',', $_GET['nr']);
    } else {
        $nrArray = $_GET['nr'];
    }

    foreach ($nrArray as $nrElement) {
        $xmlArray[$nrElement] = simplexml_load_file('BNBDaten/bnbrdf_N' . trim($nrElement) . '.rdf');
    }
} else {
    //echo "Bitte eine Nummer als Parameter spezifizieren, z.B. &nr=3231";
}


if (isset($_GET['ddc'])) {
    $ddcArray = explode(',', $_GET['ddc']);
    $query = "";
    foreach ($ddcArray as $ddcElement) {
        if ($query == "") {
            $query = 'starts-with(., \'' . trim($ddcElement) . '\') ';
        } else {
            $query .= 'or starts-with(., \'' . trim($ddcElement) . '\') ';
        }
    }
} else {
    $query = 'starts-with(.,"")';
}
//forthInclude ~ include forthcoming publication? - Yes, no, only
if (isset($_GET['forth'])) {//yes, no, only
    $forthInclude = $_GET['forth'];
} else {
    $forthInclude = "yes";
}
//noisbnInclude ~ include publication without isbn? - Yes, no, only
if (isset($_GET['noisbn'])) {//yes, no, only
    $noisbnInclude = $_GET['noisbn'];
} else {
    $noisbnInclude = "yes";
}


echo "<?xml version=\"1.0\"?>\n";
echo "<?xml-stylesheet href=\"bnb.xsl\" type=\"text/xsl\"?>\n";
echo "<bnb xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:dcterms=\"http://purl.org/dc/terms/\" xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\" xmlns:isbd=\"http://iflastandards.info/ns/isbd/elements/\" xmlns:rda=\"http://rdvocab.info/Elements\" xmlns:skos=\"http://www.w3.org/2004/02/skos/core#\" xmlns:bibo=\"http://purl.org/ontology/bibo/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:owlt=\"http://www.w3.org/2006/time#\" >\n";



foreach ($nrArray as $nrElement) {
    $xml = $xmlArray[$nrElement];

    //register namespaces for the use in the XPath-Expression for the element xml
    //(will not work for xpath-expressions starting from a different node)
    $ns = $xml->getNameSpaces(true);
    foreach ($ns as $nsName => $nsUri) {
        $xml->registerXPathNamespace($nsName, $nsUri);
    }

    //contains(.,'004.') the current node contains the string 004.
    //starts-with(.,'00') the current node starts with 00
    //more than one ddc with or in [...]
    //z.B. $xpath = $xml->xpath('//rdf:Description/dcterms:subject/rdf:Description/skos:notation[starts-with(., \'004\') or starts-with(., \'005\')  or starts-with(., \'006\') ]/../../..');
    $xpath = $xml->xpath('//rdf:Description/dcterms:subject/rdf:Description/skos:notation[' . $query . ']/../../..'); //
    //var_dump($xpath);


    $nFiltered = 0;

    foreach ($xpath as $item) {
        $itemXML = $item->asXML();
        $forthAppear = stristr($itemXML, "Forthcoming publication");
        $isbnAppear = stristr($itemXML, "isbn");
        if ($forthInclude == "yes" || ($forthInclude == "no" && !$forthAppear) || ($forthInclude == "only" && $forthAppear)) {
            if ($noisbnInclude == "yes" || ($noisbnInclude == "no" && $isbnAppear) || ($noisbnInclude == "only" && !$isbnAppear)) {
                $nFiltered++;
                echo $item->asXML();
                //echo '<hr />';
                echo "\n";
            }
        }
    }

    echo "<query>\n";
    echo "<source>bnblist$nrElement</source>\n";
    echo "<querystring>DDC $query ; include forthcoming publication: $forthInclude ; include publications without isbn: $noisbnInclude </querystring>\n";
    //filter
    echo '<nresults>' . $nFiltered . ' (' . count($xpath) . ' ungefiltert) ' . '</nresults>';
    echo "\n</query>\n";
}

echo "</bnb>";
?>
