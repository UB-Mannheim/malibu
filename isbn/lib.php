<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/isbn
 *
 * Copyright (C) 2014 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Zusammenstellung von Funktionen und gemeinsam genutzten Konstrukten
 * sowie Laden der ini Datei.
 *
 */

if (!file_exists('conf.php')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("ERROR: conf.php nicht gefunden");
}

include 'conf.php';


$standardMarcMap = array(
    'id' => '//controlfield[@tag="001"]',
    'isbn' => '//datafield[@tag="020"]/subfield[@code="a"]|//datafield[@tag="776"]/subfield[@code="z"]',
    'dnbNr' => '//datafield[@tag="016" and contains(subfield[@code="2"], "DE-101")]/subfield[@code="a"]',
    'oclcNr' => '//datafield[@tag="035"]/subfield[@code="a" and contains(text(), "(OCoLC)")]|//datafield[@tag="019"]',
    'titel' => '//datafield[@tag="245"]/subfield[@code="a"]',
    'untertitel' => '//datafield[@tag="245"]/subfield[@code="b"]',
    'autor' => '//datafield[@tag="245"]/subfield[@code="c"]',
    'gesamttitel' => '//datafield[@tag="490"]',
    'hochschulvermerk' => '//datafield[@tag="502"]/subfield',
    'auflage' => '//datafield[@tag="250"]/subfield[@code="a"]',
    'erscheinungsinfo' => '//datafield[@tag="260" or tag="270" or @tag="263" or @tag="264"]/subfield', //264 for BL added
    'umfang' => '//datafield[@tag="300"]/subfield',
    'links' => '//datafield[@tag="856" and @ind1="4"]/subfield[@code="u"]',
    'bestand' => '//datafield[@tag="LOK" and contains(subfield[@code="0"], "852")]/subfield[@code="a"]',
    'rvk' => '//datafield[@tag="084" and contains(subfield[@code="2"], "rvk")]/subfield[@code="a"]',
    'ddc' => '//datafield[@tag="082" and @ind2!="9"]/subfield[@code="a"]',
    'sw' => array(
        'mainPart' => '//datafield[starts-with(@tag,"6") and (subfield[@code="2"]="gbv" or subfield[@code="2"]="gnd")]',
        'value' => './subfield[@code="a"]',
        'subvalues' => './subfield[@code="b" or @code="t"]',
        'additional' => './subfield[@code="9" or @code="g" or @code="z"]',
        'key' => './subfield[@code="0" and contains(text(), "(DE-588)")]'
    ),
    'produktSigel' => '//datafield[@tag="912" and not(@ind2="7")]/subfield[@code="a"]',
    'vorauflage' => '//datafield[@tag="780"]/subfield[@code="z"]',
    'folgeauflage' => '//datafield[@tag="785"]/subfield[@code="z"]',
    'andererelation' => '//datafield[@tag="787" or @tag="775"]/subfield[@code="z"]'
);

$standardMabMap = array(
    'id' => '//feld[@nr="001"]',
    'isbn' => '//feld[@nr="540"]',
    'dnbNr' => '//feld[@nr="026" and contains(text(), "DNB")]',
    //'oclcNr' => '',
    'titel' => '//feld[@nr="331"]',
    'untertitel' => '//feld[@nr="335"]',
    'autor' => '//feld[@nr="359"]',
    'gesamttitel' => '//feld[@nr="451"]',
    'hochschulvermerk' => '//feld[@nr="519"]',
    'auflage' => '//feld[@nr="403"]',
    'erscheinungsinfo' => '//feld[@nr="410" or @nr="412" or @nr="425" or @nr="536"]',
    'umfang' => '//feld[@nr="433"]',
    'links' => '//feld[@nr="655"]/uf[@code="u"]',
    'bestand' => '//feld[@nr="088"]/uf[@code="a"]',
    'rvk' => '//feld[@nr="700" and @ind="g"]',
    'ddc' => '//feld[@nr="700" and @ind="b"]|//feld[@nr="705"]/uf[@code="a"]',
    'sw' => '//feld[@nr="902" or @nr="907" or @nr="912" or @nr="917" or @nr="922" or @nr="927" or @nr="932" or @nr="937" or @nr="942" or @nr="947" ]',
    'produktSigel' => '//feld[@nr="078" and @ind="e"]/uf[@code="a"]',
);

function performMapping($map, $outputXml)
{
    $outputMap = [];
    foreach ($map as $label => $xpath) {
        //$xpath = '//datafield[@tag="020"]/subfield[@code="a"]';
        if (is_string($xpath)) {
            $values = $outputXml->xpath($xpath);
            if ($values) {
                $values = array_map("getValues", $values);
                $values = array_unique($values);
                //beim array_unique sind die keys nicht mehr unbedingt aufeinanderfolgend,
                //daher kopieren wir die Werte in ein neues Array:
                $values = array_values($values);
                $outputMap[$label] = $values;
            } else {
                $outputMap[$label] = '';
            }
        } else {//$label, $xpath['mainPart'], $xpath['value'], $xpath['key']
            $mainPart = $outputXml->xpath($xpath['mainPart']);
            $outputArray = [];
            foreach ($mainPart as $singleMainPart) {
                $value = $singleMainPart->xpath($xpath['value']);
                $key = $singleMainPart->xpath($xpath['key']);
                if ($value) {
                    $valueText = getValues($value[0]);
                    if (array_key_exists('subvalues', $xpath)) {
                        $subvalues = $singleMainPart->xpath($xpath['subvalues']);
                        $subvaluesArray = [$valueText];
                        foreach ($subvalues as $sub) {
                            array_push($subvaluesArray, getValues($sub));
                        }
                        $valueText = implode('. ', $subvaluesArray);
                    }
                    if (array_key_exists('additional', $xpath)) {
                        $additional = $singleMainPart->xpath($xpath['additional']);
                        if ($additional) {
                            $additionalText = getValues($additional[0]);
                            if (strpos($additionalText, ':') == 1) {
                                $additionalText = substr($additionalText, 2);
                            }
			    $valueText = $valueText . ' <' . $additionalText . '>';
                        }
                    }

                    if ($key) {
                        $outputArray[$valueText] = getValues($key[0]);
                    } else {
                        $outputArray[$valueText] = true;
                    }
                }
            }
            $outputMap[$label] = $outputArray;

        }

    }
    return cleanUp($outputMap);
}

function cleanUp($outputMap)
{
    if (is_array($outputMap['bestand'])) {
        foreach ($outputMap['bestand'] as $i => $sigel) {
            $outputMap['bestand'][$i] = str_replace('DE-', '', $sigel);
        }
    }
    if (is_array($outputMap['sw'])) {
        foreach ($outputMap['sw'] as $index => $value) {
            $affixes = array('11|', '1|', '|', '(DE-588)'); //the order is important here
            if (is_string($value)) {
                /* e.g. $value $index = ...
                "Haustiere": "4023819-2",
                "Anatomie": "4001895-7",
                "Physiologie": "4045981-0",
                "Lehrbuch": true
                */
                $value = trim(str_replace($affixes, '', $value));
                $outputMap['sw'][$index] = $value;
            }
            if (is_int($index)) {
                /* e.g. $value = ...
                "  4023819-2           Haustiere"
                "  4001895-7           Anatomie"
                "  4045981-0           Physiologie"
                "  |Lehrbuch"
                */
                if (preg_match('/^\s*([0123456789Xx-]+)\s+(\S.*)$/', $value, $treffer)) {
                    $outputMap['sw'][$treffer[2]] = $treffer[1];
                } else {
                    $value = trim(str_replace($affixes, '', $value));
                    $outputMap['sw'][$value] = true;
                }
                unset($outputMap['sw'][$index]);
            }
            if (is_string($index) && is_bool($value)) {
                /* e.g.
                $index = "11|Comic"
                $value = true
                */
                unset($outputMap['sw'][$index]);
                $indexNew = trim(str_replace($affixes, '', $index));
                $outputMap['sw'][$indexNew] = $value;
            }
        }
    }
    if (is_array($outputMap['gesamttitel'])) {
        foreach ($outputMap['gesamttitel'] as $i => $gesamttitel) {
            if (is_string($gesamttitel)) {
                $outputMap['gesamttitel'][$i] = trim(preg_replace('/\s+/', ' ', $gesamttitel));
            }
        }
    }
    if (is_array($outputMap['rvk'])) {
        foreach ($outputMap['rvk'] as $i => $value) {
            //e.g. <feld nr="700" ind="g">|SK 830<tf/>Automatisch aus BVB_2013-06 2013-03-27</feld>
            $value = explode('Automatisch aus', $value)[0];
            $outputMap['rvk'][$i] = str_replace('|', '', $value);
        }
    }
    if (is_array($outputMap['ddc'])) {
        foreach ($outputMap['ddc'] as $i => $value) {
            $outputMap['ddc'][$i] = preg_replace('/[^\d\.]/', '', $value); //str_replace('|', '', str_replace('/', '', $value));
        }
    }
    if (array_key_exists('oclcNr', $outputMap) && is_array($outputMap['oclcNr'])) {
        foreach ($outputMap['oclcNr'] as $i => $value) {
            $outputMap['oclcNr'][$i] = preg_replace('/[^\d]/', '', $value);
        }
    }
    if (is_array($outputMap['isbn'])) {
        foreach ($outputMap['isbn'] as $i => $value) {
            $outputMap['isbn'][$i] = trim(preg_replace('/[^\d\s]/', '', $value));
        }
    }
    if (is_array($outputMap['dnbNr'])) {
        foreach ($outputMap['dnbNr'] as $i => $value) {
            $outputMap['dnbNr'][$i] = str_replace('DNB', '', $value);
        }
    }
    if (is_array($outputMap['links'])) {
        foreach ($outputMap['links'] as $i => $value) {
            if (substr($value, 0, 4) !== "http") {
                $outputMap['links'][$i] = "http://" . $value;
            }
        }
    }

    return $outputMap;
}


function getValues($xmlObject)
{
    //return (string)$xmlObject;
    return dom_import_simplexml($xmlObject)->textContent;
}


// für MAB als XML Ausgabe
function printLine($line)
{
    $output = '';
    //nicht umgesetzt wurden: Stichwortzeichen <stw></stw>, Nichtsortierzeichen -> <ns></ns>
    if (strlen($line) > 1) {//Am Ende gibt es ein Satzendezeichen 1E, damit hat der String die Groesse 1.
        $nr  = substr($line, 0, 3);
        $ind = substr($line, 3, 1);
        $inhalt = substr($line, 4);
        if (strstr($inhalt, "\x1f")) {
            $lineArray = explode("\x1f", $inhalt);
            $output .= "<feld nr='$nr' ind='$ind'>\n";
            for ($k = 1; $k < count($lineArray); $k++) {
                    $output .= '<uf code="' . substr($lineArray[$k], 0, 1) . '">' . printMabContent(substr($lineArray[$k], 1)) . '</uf>' . "\n";
            }
            $output .= "</feld>";
        } else {
            $output .= '<feld nr="' . $nr . '" ind="' . $ind . '">' . printMabContent($inhalt) . '</feld>' . "\n";
        }
    }
    return $output;
}


function printMabContent($content)
{
    $tf = "\xE2\x80\xA1"; //Teilfeldtrennzeichen
    if (strpos($content, $tf) !== false) {
        $feldArray = explode($tf, $content);
        for ($l = 1; $l < count($feldArray); $l++) {
            $feldArray[$l] = htmlspecialchars($feldArray[$l], ENT_XML1);
        }
        return implode('<tf/>', $feldArray);
    } else {
        return htmlspecialchars($content, ENT_XML1);
    }
}

/* Alternative:
function printMabContent($content)
{
    return htmlspecialchars($content, ENT_XML1);
}
*/
