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
 * Zusammenstellung von Javascript Funktionen hauptsächlich zur
 * Darstellung von den einzelnen Informationen.
 */


//Standard Rendering
function render(someArray, delimiter) {
    //delimiter is optional and if not set single space will be used
    delimiter = typeof delimiter !== 'undefined' ? delimiter : ' ';
    if (typeof someArray === 'string') {
        return someArray;
    } else {
        return someArray.join(delimiter);
    }
}

function renderRVK(rvkArray) {
    if (typeof rvkArray === 'string') {
        return rvkArray;
    } else {
        for (var i=0; i<rvkArray.length; i++) {
            var rvk = rvkArray[i];
            var rvkUrl = 'http://rvk.uni-regensburg.de/notation/' + rvk.replace(' ', '%20');
            rvkArray[i] = '<a href="' + rvkUrl + '" target="_blank" class="' + rvk.replace(' ', '-') + '">' + rvk + '</a>';
            // Die Klassenbezeichnung hier benützen wir später für das
            // dazuladen der Notationen im title-Attribute.
        }
        return rvkArray.join(', ');
    }
}
function renderSigel(sigelArray){
	var sigelMitZDBAlsLinks = "";
	var trenner = ", ";
	for (var i=0; i<sigelArray.length; i++) { 
		if (sigelArray[i].startsWith("ZDB-")) { // render Sigel starting with ZDB- as links
			sigelMitZDBAlsLinks += '<a href="http://sigel.staatsbibliothek-berlin.de/nc/suche/?isil='+ sigelArray[i] + '" '  + 'target="_blank">' + sigelArray[i] + '</a>';
		} else { // render other Sigel normally
			sigelMitZDBAlsLinks += sigelArray[i];
		}
		if (i+1 < sigelArray.length) { //append separator if it is not the last item
			sigelMitZDBAlsLinks += trenner; 
		}
	}
	return sigelMitZDBAlsLinks;
}

//JSONP ist immer asynchron. Daher folgender Trick:
//Die Links erhalten gemaess ihrer RVK eine Klassenbezeichnung.
//Dann wird JSONP irgendwann fertig sein und in diese Links
//das Attribut title auf die entsprechende Benennung setzen.
//Wichtig ist, dass die Link bereits da sein müssen, bevor
//die Funktion hier aufgeruft wird.
function addBenennung(index, element) {
    var className = $(element).attr('class');
    //z.B. https://rvk.uni-regensburg.de/api/json/ancestors/SU+680?jsonp=wrapper
    var rvkApi = 'https://rvk.uni-regensburg.de/api/json/ancestors/' + className.replace('-', '+') + '?jsonp=?';
    $.getJSON(rvkApi, function(json) {
        if (json.node.ancestor) {//Benennung des Knoten + Benennung des Vorfahrens
            $('.'+className).attr("title", json.node.benennung + ' <-- ' + json.node.ancestor.node.benennung);
        } else {//Benennung des Knoten
            $('.'+className).attr("title", json.node.benennung);
        }
    });
}

function renderDDC(ddcArray) {
    if (typeof ddcArray === 'string') {
        return ddcArray;
    } else {
        for (var i=0; i<ddcArray.length; i++) {
            var ddc = ddcArray[i];
            //var ddcUrl = 'http://dewey.info/class/' + ddc + '/';
            var ddcUrl = 'http://deweysearchde.pansoft.de/webdeweysearch/executeSearch.html?lastScheduleRecord=' + ddc + '&lastTableRecord=&query=' + ddc;
            ddcArray[i] = '<a href="' + ddcUrl + '" target="_blank">' + ddc + '</a>';
        }
        return ddcArray.join(', ');
    }
}

var paketInfoMap = [];// kann um die lokalen Pakete ergänzt werden
var overallPaketSigel = [];//alle bereits gefunden Sigel werden in einer Liste gespeichert

function renderPS(psArray) {// PS = Produktsigel
    if (typeof psArray === 'string') {
        return psArray;
    } else {
        var outputArray = [];
        for (var i=0; i<psArray.length; i++) {
            var ps = psArray[i];
            var psCheck = '';
            var color = 'ffffff';
            if (overallPaketSigel[ps]) {//um Doppelungen zu vermeiden
                continue;
            }
            overallPaketSigel[ps] = true;
            if (paketInfoMap[ps]) {
                psCheck = paketInfoMap[ps];
                color = 'CC99FF';
            }
            outputArray.push('<span title="' + psCheck + '"  style="background-color:#' + color + '">' + ps + '</span>');
        }
        if (outputArray.length > 0) {
            return ' | '+outputArray.join(' | ');
        } else {
            return '';
        }
    }
}

//alle bereits gefundenen Links werden in ein Array gespeichert
var overallLinks = [];

function renderLinks(linkArray) {
    if (typeof linkArray === 'string') {
        return linkArray;
    } else {
        var outputArray = [];
        for (var i=0; i<linkArray.length; i++) {
            var link = linkArray[i];
            if (overallLinks[link]) {//um Doppelungen zu vermeiden
                continue;
            }
            overallLinks[link] = true;
            outputArray[i] = '<a href="' + link + '" target="_blank">' + link + "</a><br/>\n";
        }
        return outputArray.join('');
    }
}

function renderBestandSWB(bestandArray, id) {
    var bibArray = $.map(bestandArray, function(sigel) {
        if (sigel === "180") {
            return '<span style="border:2px solid red">180</span>';
        } else {
            return sigel;
        }
    });
    var outputString = "Insgesamt "+bibArray.length+" Bibliotheken im <a href='http://swb.bsz-bw.de/DB=2.1/PPNSET?PPN="+id+"&INDEXSET=21' target='_blank'>SWB</a> mit Bestand: "+bibArray.join(", ");
    return outputString;
}


//Einfache Ersetzung von einigen speziellen Zeichen
//zur Darstellung als Text in HTML. Insbesondere bei
//Homonymzusätzen.
function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}


function renderSW(swObject) {
    var swArray = [];
    $.each(swObject, function(key, value) {
        if(typeof value == 'string') {
            var swUrl = 'http://d-nb.info/gnd/' + value + '/about/html';
            swArray.push('<a href="' + swUrl + '" target="_blank">' + htmlEscape(key) + '</a>');
        } else {
            swArray.push(key);
        }
    });
    return swArray.join(', ');
}

function bestellInfo(databaseText, currentRecord) {
    var content = databaseText+currentRecord.id+"\n";
    content += 'ISBN: ' + render(currentRecord.isbn, ', ') + "\n";
    content += render(currentRecord.titel) + ' ' + render(currentRecord.untertitel) + ' ' + render(currentRecord.autor);
    if (currentRecord.gesamttitel) {
        content += ' ( ' + render(currentRecord.gesamttitel) + ' )';
    }
    content += '; ' + render(currentRecord.auflage);
    content += '; ' + render(currentRecord.erscheinungsinfo)+ ' ' + render(currentRecord.hochschulvermerk);
    content += '; ' + render(currentRecord.umfang);

    return content;
}

function coins(currentRecord) {
    // rudimentärer COinS Daten um beispielsweise Titel in Citavi, Zotero oder Mendeley zu speichern
    // und darüber Bestellungen zu verwalten:
    var coins = '<span class="Z3988" title="url_ver=Z39.88-2004&amp;ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';
    if (currentRecord.titel) { coins += '&amp;rft.btitle=' + encodeURIComponent(currentRecord.titel); }
    if (currentRecord.erscheinungsinfo) { coins += '&amp;rft.publisher=' + encodeURIComponent(currentRecord.erscheinungsinfo[1]); }
    if (currentRecord.erscheinungsinfo) { coins += '&amp;rft.isbn=' + encodeURIComponent(currentRecord.isbn); }
    if (currentRecord.auflage) { coins += '&amp;rft.edition=' + encodeURIComponent(currentRecord.auflage); }
    if (currentRecord.autor) { coins += '&amp;rft.au=' + encodeURIComponent(currentRecord.autor); }
    if (currentRecord.erscheinungsinfo) { coins += '&amp;rft.date=' + encodeURIComponent(currentRecord.erscheinungsinfo[2]); }
    coins += '"></span>';

    return coins;
}
 

function getParameterByName(name) {
/*
 * Parameter als URL-Anhaenge werden direkt
 * ueber ihren Namen ansprechbar. Z.B.
 * bei liste-se.html?isbn=0521518148
 * getParameterByName("isbn") = 0521518148
 */
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

 
function isbn10(z) {
/*
 * Umwandlung in eine 10-stellige ISBN,
 * wobei die Eingabe entweder bereits
 * eine 10-stellige ISBN oder eine 13-
 * stellige ISBN sein muss.
 */
    if (z.length === 13) {
        var t = ( z.substr(3, 1) + 2 * z.substr(4, 1) + 3 * z.substr(5, 1) + 4 * z.substr(6, 1) + 5 * z.substr(7, 1) + 6 * z.substr(8, 1) + 7 * z.substr(9, 1) + 8 * z.substr(10, 1) + 9 * z.substr(11, 1) ) % 11;
        if (t === 10) {
            t = 'X';
        }
        return z.substr(3, 9) + t;
    } else {
        return z;
    }
}

function isbn13(z) {
/*
 * Umwandlung in eine 13-stellige ISBN,
 * wobei die Eingabe entweder bereits
 * eine 13-stellige ISBN oder eine 10-
 * stellige ISBN sein muss.
 */
    z = z.replace(/-/g, "").replace(/ /g, "").replace(/x/g, "X");
    if (z.length === 10) {
        z = '978' + z.substr(0, 9);
        var t = (10 - (( z.substr(0, 1) + 3 * z.substr(1, 1) + z.substr(2, 1) + 3 * z.substr(3, 1) + z.substr(4, 1) + 3 * z.substr(5, 1) + z.substr(6, 1) + 3 * z.substr(7, 1) + z.substr(8, 1) + 3 * z.substr(9, 1) + z.substr(10, 1) + 3 * z.substr(11, 1) ) % 10 )) % 10;
        return z + t;
    } else {
        return z;
    }
}
