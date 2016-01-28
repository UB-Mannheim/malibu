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
function isbn10($z) {
    if (strlen($z) == 13) {
        $t = (substr($z, 3, 1) . 2 * substr($z, 4, 1) . 3 * substr($z, 5, 1) . 4 * substr($z, 6, 1) .
                5 * substr($z, 7, 1) . 6 * substr($z, 8, 1) . 7 * substr($z, 9, 1) . 8 * substr($z, 10, 1) .
                9 * substr($z, 11, 1) ) % 11;
        if ($t == 10) {
            $t = 'X';
        }
        return substr($z, 3, 9) . $t;
    } else {
        return $z;
    }
}

function isbn13($z) {
    if (strlen($z) == 10) {
        $z = '978' . substr($z, 0, 9);
        $t = (10 - ((substr($z, 0, 1) . 3 * substr($z, 1, 1) . substr($z, 2, 1) . 3 * substr($z, 3, 1) .
                     substr($z, 4, 1) . 3 * substr($z, 5, 1) . substr($z, 6, 1) . 3 * substr($z, 7, 1) .
                     substr($z, 8, 1) . 3 * substr($z, 9, 1) . substr($z, 10, 1) . 3 * substr($z, 11, 1)) % 10)) % 10;
        return $z . $t;
    } else {
        return $z;
    }
}



if (isset($_GET['isbn10'])) {
    global $n10, $n13;
    $n10 = trim($_GET['isbn10']);//fuer Amazon
    if (strlen($n10) != 10) {
        exit("ISBN war nicht 10-stellig!");
    }
    $n13 = isbn13($n10);
}
if (isset($_GET['isbn13'])) {
    global $n10, $n13;
    $n13 = trim($_GET['isbn13']);//fuer Google
    if (strlen($n13) != 13) {
        exit("ISBN war nicht 13-stellig!<br/>");
    }
    $n10 = isbn10($n13);
}

if ( !isset($_GET['isbn13']) && !isset($_GET['isbn10']) ) {
    exit("isbn13 und isbn10 fehlen");
}


$urlAmazon = 'http://www.amazon.de/dp/' . $n10;
$headerAmazon = get_headers($urlAmazon, 1);//$headerAmazon[0] is a String, e.g. HTTP/1.1 404 NotFound ; HTTP/1.1 200 OK
$foundOnAmazon = !strpos($headerAmazon[0], '404 NotFound');
$docAmazon = new DOMDocument();

if ($foundOnAmazon) {
    libxml_use_internal_errors(true);//HTML5 erzeugt Warnings beim Einlesen, aber die Option beseitigt dies
    $docAmazon->loadHTMLFile($urlAmazon);
    libxml_use_internal_errors(false);
    //$contentAmazon = $docAmazon->saveHTML();
    
    //Bild von Amazon
    foreach (["imgBlkFront", "main-image-nonjs", "original-main-image" ] as $id) {
        if ( $docAmazon->getElementById($id) && $docAmazon->getElementById($id)->getAttribute('src') !== '' ) {
            $cover = $docAmazon->getElementById($id)->getAttribute('src');
            $coverOrigin = $urlAmazon;
            break;
        }
    }
    
    //Beschreibung von Amazon
    if ( $docAmazon->getElementById('bookDesc_override_CSS') && $docAmazon->getElementById('bookDesc_override_CSS')->nextSibling && $docAmazon->getElementById('bookDesc_override_CSS')->nextSibling->textContent !== '' ) {
        $description = $docAmazon->getElementById('bookDesc_override_CSS')->nextSibling->textContent;
        $descriptionOrigin = $urlAmazon;
    } else if ($docAmazon->getElementById('postBodyPS')) {
        $description = $docAmazon->getElementById('postBodyPS')->textContent;
        $descriptionOrigin = $urlAmazon;
    } else if ($docAmazon->getElementById('iframeContent')) {
        $description = $docAmazon->getElementById('iframeContent')->textContent;
        $descriptionOrigin = $urlAmazon;
    }

    //Preis von Amazon
    if ( $docAmazon->getElementById('fbt_item_data') ) {
        $hiddenItemData = $docAmazon->getElementById('fbt_item_data')->textContent;
        //..."buyingPrice":79.99,"ASIN":"3830493665"...
        if ( preg_match('/"buyingPrice":(.*),"ASIN":"' . $n10 . '"/', $hiddenItemData, $match) ) {
            preg_match('/"currencyCode":"([^"]*)"/', $hiddenItemData, $descriptionCurrency);
            $price =  $match[1] . ' ' . $descriptionCurrency[1];
            $priceOrigin = $urlAmazon;
        }
    } else if ( $docAmazon->getElementById('buyNewSection') ) {
        $price = trim($docAmazon->getElementById('buyNewSection')->textContent );
        $priceOrigin = $urlAmazon;
    }
    
    //Bewertung von Amazon
    if ( $docAmazon->getElementById('acrPopover') ) {
        $ratingValue = $docAmazon->getElementById('acrPopover')->getAttribute('title');
        $numberOfReviews = $docAmazon->getElementById('acrCustomerReviewText')->textContent;
        $rating = $ratingValue . ' (' . $numberOfReviews . ')';
        $ratingOrigin = 'http://www.amazon.de/product-reviews/' . $n10;
    } else if ( $docAmazon->getElementById('revFMSR') ) {
        $ratingValue = $docAmazon->getElementById('revFMSR')->getElementsByTagName('a')->item(0)->getAttribute('title');
        $numberOfReviews = trim( str_replace('Amazon.com:' , '' , $docAmazon->getElementById('revFMSR')->textContent ) );
        $rating = $ratingValue . ' (' . $numberOfReviews . ')';
        $ratingOrigin = 'http://www.amazon.com/product-reviews/' . $n10;
    }
    
}


$urlGoogle = "https://www.googleapis.com/books/v1/volumes?q=ISBN:$n13";
$headerGoogle = get_headers($urlGoogle, 1);
$foundOnGoogle = !strpos($headerGoogle[0], '404 NotFound');

if ($foundOnGoogle) {
    $contentGoogle = file_get_contents($urlGoogle);
    $jsonGoogle = json_decode($contentGoogle);

    if (property_exists($jsonGoogle, 'items')) {
        for ($j=0; $j<count($jsonGoogle->items); $j++) {
            //die Suche welche durch die URL $urlGoogle ausgeführt wird, liefert auch falsche Ergebnisse (welche die ISBN nur in einer angehängten URL enthalten --> darum muss man hier nochmals genau filtern
            $volumeInfo = $jsonGoogle->items[$j]->volumeInfo;
            if ($volumeInfo && property_exists($volumeInfo, "industryIdentifiers")) {
                for ($k=0; $k<count($volumeInfo->industryIdentifiers); $k++) {
                    if ($volumeInfo->industryIdentifiers[$k]->identifier == $n13 || $volumeInfo->industryIdentifiers[$k]->identifier == $n13) {
                        $urlGoogle = $volumeInfo->infoLink;
                        //Bild von Google
                        if (!isset($cover) && property_exists($volumeInfo, 'imageLinks') && property_exists($volumeInfo->imageLinks, 'thumbnail') ) {
                            $cover = $volumeInfo->imageLinks->thumbnail ;
                            $coverOrigin = $urlGoogle;
                        }
                        
                        //Beschreibung von Google
                        if (!isset($description) && property_exists($volumeInfo, 'description') ) {
                            $description = $volumeInfo->description ;
                            $descriptionOrigin = $urlGoogle;
                        }
                        
                        //Bewertung von Google
                        if (!isset($rating) && property_exists($volumeInfo, 'averageRating') ) {
                            $rating = $volumeInfo->averageRating . ' von 5 (' . $volumeInfo->ratingsCount . ' Ratings)' ;
                            $ratingOrigin = $urlGoogle;
                        } 
                    }
                }
            }
        }
    }
}


if (!isset($cover)) {
    $urlOpenlibrary = 'http://covers.openlibrary.org/b/isbn/' . $n13 . '-M.jpg';
    $headerOpenlibrary = get_headers($urlOpenlibrary, 1);
    if ( !strpos($headerOpenlibrary[0], '404 NotFound') ) {
        $cover = $urlOpenlibrary;
        $coverOrigin = 'https://openlibrary.org/isbn/' . $n13;
    }
}



echo '<table border="0" width="100%">';
echo '<tr><td>';

//Cover anzeigen
if (isset($cover)) {
    echo '<a href="'. $coverOrigin .'" target="_blank"><img src="' . $cover . '"/></a><br />';
}
//Direktlinks zu Amazon, GoogleBooks
echo '<a href="' . $urlAmazon . '" target="_blank">AmazonDE</a> ; <a href="http://www.amazon.com/dp/' . $n10 . '" target="_blank">AmazonCOM</a> ; <a href="' . $urlGoogle . '" target="_blank">GoogleBooks</a>';

echo '</td><td>';

//Beschreibung
if (isset($description)) {
    echo $description . '(Quelle: <a href="' . $descriptionOrigin . '" target="_blank">' . $descriptionOrigin . '</a>)';
}

echo '</td><td>';

//Preis
if (isset($price)) {
    echo $price . '<br/>';
}
//Bewertung
if (isset($rating)) {
    echo '<a href="' . $ratingOrigin . '" target="_blank">' . $rating  . '</a><br/>';
}

echo '</td></tr></table>';
