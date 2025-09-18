<?php
/*
 * Copyright (C) 2013 Universitätsbibliothek Mannheim
 *
 * Author:
 *    Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>
 *
 * This is free software licensed under the terms of the GNU GPL,
 * version 3, or (at your option) any later version.
 * See <http://www.gnu.org/licenses/> for more details.
 *
 * Aufruf/Einbindung: Für den Aufruf wird entweder die
 * 10-stellige oder 13-stellige ISBN benötigt und als
 * Parameter angehängt:
 *    @isbn13
 *    @isbn10
 *
 * Beispiel a: verkaufsinfo.php?isbn13=9783825239244
 * Beispiel b: verkaufsinfo.php?isbn10=3836227312
 *
 * Beschreibung: Diese php Datei gibt ein HTML-Fragment
 * mit Verkaufsinformationen (Cover, Beschreibung, Preis, Bewertung)
 * als Gesamtdarstellung wieder. Hierbei werden einige gängige
 * Anbieter dafür abgefragt.
 */

include 'conf.php';
include 'lib.php';

if (isset($_GET['isbn10'])) {
    global $n10, $n13;
    $n10 = trim($_GET['isbn10']); //fuer Amazon
    if (strlen($n10) != 10) {
        header('HTTP/1.1 400 Bad Request');
        exit("ISBN war nicht 10-stellig!");
    }
    $n13 = isbn13($n10);
}
if (isset($_GET['isbn13'])) {
    global $n10, $n13;
    $n13 = trim($_GET['isbn13']); //fuer Google
    if (strlen($n13) != 13) {
        header('HTTP/1.1 400 Bad Request');
        exit("ISBN war nicht 13-stellig!<br/>");
    }
    $n10 = isbn10($n13);
}

if (!isset($_GET['isbn13']) && !isset($_GET['isbn10'])) {
    header('HTTP/1.1 400 Bad Request');
    exit("isbn13 und isbn10 fehlen");
}


$urlAmazon = 'https://www.amazon.de/dp/' . $n10;
if (status_ok($urlAmazon)) {
    $docAmazon = new DOMDocument();
    libxml_use_internal_errors(true); //HTML5 erzeugt Warnings beim Einlesen, aber die Option beseitigt dies
    $docAmazon->loadHTMLFile($urlAmazon);
    libxml_use_internal_errors(false);
    //$contentAmazon = $docAmazon->saveHTML();


    //Bild von Amazon
    foreach (["imgBlkFront", "main-image-nonjs", "original-main-image"] as $id) {
        if ($docAmazon->getElementById($id) &&
            $docAmazon->getElementById($id)->getAttribute('src') !== '') {
            $cover = $docAmazon->getElementById($id)->getAttribute('src');
            $coverOrigin = $urlAmazon;
            break;
        }
    }

    //Beschreibung von Amazon
    if ($docAmazon->getElementById('bookDesc_override_CSS') &&
        $docAmazon->getElementById('bookDesc_override_CSS')->nextSibling) {
        $node = $docAmazon->getElementById('bookDesc_override_CSS');
        // description is expected inside the next following tag named "noscript"
        $i = 0;
        while ($node && $i < 5) {
            if ($node->nodeName == "noscript") {
                $description = $node->textContent;
                break;
            }
            $node = $node->nextSibling;
            $i++;
        }
        $descriptionOrigin = $urlAmazon;
    }
    if (empty($description)) {
        foreach (['postBodyPS', 'iframeContent', 'bookDescription_feature_div'] as $id) {
            if ($docAmazon->getElementById($id)) {
                $description = $docAmazon->getElementById($id)->textContent;
                $descriptionOrigin = $urlAmazon;
                break;
            }
        }
    }

    //Preis von Amazon
    if ($docAmazon->getElementById('fbt_item_data')) {
        $hiddenItemData = $docAmazon->getElementById('fbt_item_data')->textContent;
        //..."buyingPrice":79.99,"ASIN":"3830493665"...
        if (preg_match(
            '/"buyingPrice":(.*),"ASIN":"' . $n10 . '"/',
            $hiddenItemData,
            $match
        )) {
            preg_match('/"currencyCode":"([^"]*)"/', $hiddenItemData, $descriptionCurrency);
            $price = $match[1] . ' ' . $descriptionCurrency[1];
            $priceOrigin = $urlAmazon;
        }
    } else {
        foreach (['buyNewSection', 'price', 'corePrice_feature_div'] as $id) {
            if ($docAmazon->getElementById($id)) {
                $price = trim($docAmazon->getElementById($id)->textContent);
                $priceOrigin = $urlAmazon;
                break;
            }
        }
    }

    //Bewertung von Amazon
    if ($docAmazon->getElementById('acrPopover')) {
        $ratingValue = $docAmazon->getElementById('acrPopover')->getAttribute('title');
        $numberOfReviews = $docAmazon->getElementById('acrCustomerReviewText')->textContent;
        $rating = $ratingValue . ' (' . $numberOfReviews . ')';
        $ratingOrigin = 'https://www.amazon.de/product-reviews/' . $n10;
    } elseif ($docAmazon->getElementById('revFMSR')) {
        $ratingValue = $docAmazon->getElementById('revFMSR')
                                 ->getElementsByTagName('a')
                                 ->item(0)
                                 ->getAttribute('title');
        $textTotal = $docAmazon->getElementById('revFMSR')->textContent;
        if (preg_match('/\d+\s(?:Rezension|Rezensionen)/', $textTotal, $treffer)) {
            $numberOfReviews = $treffer[0];
        }
        $rating = $ratingValue . ' (' . $numberOfReviews . ')';
        $ratingOrigin = 'https://www.amazon.com/product-reviews/' . $n10;
    }
}


$urlGoogle = "https://www.googleapis.com/books/v1/volumes?q=ISBN:$n13";
if (status_ok($urlGoogle)) {
    $contentGoogle = file_get_contents($urlGoogle);
    $jsonGoogle = json_decode($contentGoogle);

    if (!is_null($jsonGoogle) && property_exists($jsonGoogle, 'items')) {
        for ($j = 0; $j < count($jsonGoogle->items); $j++) {
            // die Suche welche durch die URL $urlGoogle ausgeführt wird,
            // liefert auch falsche Ergebnisse (welche die ISBN nur in einer angehängten URL
            // enthalten --> darum muss man hier nochmals genau filtern
            $volumeInfo = $jsonGoogle->items[$j]->volumeInfo;
            if ($volumeInfo && property_exists($volumeInfo, "industryIdentifiers")) {
                for ($k = 0; $k < count($volumeInfo->industryIdentifiers); $k++) {
                    if ($volumeInfo->industryIdentifiers[$k]->identifier == $n13 ||
                        $volumeInfo->industryIdentifiers[$k]->identifier == $n13) {
                        $urlGoogle = $volumeInfo->infoLink;
                        //Bild von Google
                        if (!isset($cover) && property_exists($volumeInfo, 'imageLinks') &&
                            property_exists($volumeInfo->imageLinks, 'thumbnail')) {
                            $cover = $volumeInfo->imageLinks->thumbnail;
                            $coverOrigin = $urlGoogle;
                        }

                        //Beschreibung von Google
                        if (!isset($description) &&
                            property_exists($volumeInfo, 'description')) {
                            $description = $volumeInfo->description;
                            $descriptionOrigin = $urlGoogle;
                        }

                        //Bewertung von Google
                        if (!isset($rating) &&
                            property_exists($volumeInfo, 'averageRating')) {
                            $rating = $volumeInfo->averageRating . ' von 5 ('
                                    . $volumeInfo->ratingsCount . ' Ratings)';
                            $ratingOrigin = $urlGoogle;
                        }
                    }
                }
            }
        }
    }
}


if (!isset($cover)) {
    $urlOpenlibrary = 'https://covers.openlibrary.org/b/isbn/' . $n13 . '-M.jpg';
    $headerOpenlibrary = @get_headers($urlOpenlibrary, 1);
    if ($headerOpenlibrary !== false && !strpos($headerOpenlibrary[0], '404 NotFound')) {
        $cover = $urlOpenlibrary;
        $coverOrigin = 'https://openlibrary.org/isbn/' . $n13;
    }
}



echo '<table border="0" width="100%">';
echo '<tr><td>';

//Cover anzeigen
if (isset($cover)) {
    echo '<a href="' . $coverOrigin . '" target="_blank"><img src="' . $cover
                     . '"/></a><br />';
}
//Direktlinks zu Amazon, GoogleBooks
echo '<a href="' . $urlAmazon
    . '" target="_blank">AmazonDE</a> ; <a href="https://www.amazon.com/dp/' . $n10
    . '" target="_blank">AmazonCOM</a> ; <a href="' . $urlGoogle
    . '" target="_blank">GoogleBooks</a>';

echo '</td><td>';

//Beschreibung
if (isset($description)) {
    echo $description . '(Quelle: <a href="' . $descriptionOrigin . '" target="_blank">'
                      . $descriptionOrigin . '</a>)';
}

echo '</td><td>';

//Preis
if (isset($price)) {
    $price_array = explode("€", $price);
    echo implode("€ / ", array_unique(array_filter($price_array))) . '€<br/>';
}
//Bewertung
if (isset($rating)) {
    echo '<a href="' . $ratingOrigin . '" target="_blank">' . $rating . '</a><br/>';
}

echo '</td></tr></table>';
