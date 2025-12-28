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
function render(someArray, delimiter)
{
    //delimiter is optional and if not set single space will be used
    delimiter = typeof delimiter !== 'undefined' ? delimiter : ' ';
    if (typeof someArray === 'string') {
        return someArray;
    } else {
        return someArray.join(delimiter);
    }
}

function renderRVK(rvkArray)
{
    if (typeof rvkArray === 'string') {
        return rvkArray;
    } else {
        for (var i=0; i<rvkArray.length; i++) {
            var rvk = rvkArray[i];
            var rvkUrl = 'https://rvk.uni-regensburg.de/notation/' + encodeURI(rvk);
            rvkArray[i] = '<a href="' + rvkUrl + '" target="_blank" class="' + rvk.replace(' ', '-') + '">' + rvk + '</a>';
            // Die Klassenbezeichnung hier benützen wir später für das
            // dazuladen der Notationen im title-Attribute.
        }
        return rvkArray.sort().join(', ');
    }
}
function renderSigel(sigelArray)
{
    var sigelMitZDBAlsLinks = "";
    var trenner = ", ";
    for (var i=0; i<sigelArray.length; i++) {
        if (sigelArray[i].startsWith("ZDB-")) { // render Sigel starting with ZDB- as links
            sigelMitZDBAlsLinks += '<a href="https://sigel.staatsbibliothek-berlin.de/suche/?isil='+ sigelArray[i] + '" '  + 'target="_blank">' + sigelArray[i] + '</a>';
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
function addBenennung(index, element)
{
    var className = $(element).attr('class');
    //z.B. https://rvk.uni-regensburg.de/api/json/ancestors/SU+680?jsonp=wrapper
    var rvkApi = 'https://rvk.uni-regensburg.de/api/json/ancestors/' + className.replace('-', '+') + '?jsonp=?';
    $.getJSON(rvkApi, function (json) {
        if ("node" in json) {
            if (json.node.ancestor) {//Benennung des Knoten + Benennung des Vorfahrens
                $('.'+className).attr("title", json.node.benennung + ' <-- ' + json.node.ancestor.node.benennung);
            } else {//Benennung des Knoten
                $('.'+className).attr("title", json.node.benennung);
            }
        } else if ("error-message" in json) {
            $('.'+className).attr("title", "ERROR: "+json['error-message']);
            $('.'+className).addClass("rvkError");
        }
        $('.'+className).attr("data-json", JSON.stringify(json));
        aggregateRVK();
    });
}

function aggregateRVK() {
    var rvkNodes = $(".rvk a");
    var frequencies = {};
    var rvkExample = {};
    for (let rvkNode of rvkNodes) {
        let rvk = rvkNode.textContent;
        frequencies[rvk] = frequencies[rvk] ? frequencies[rvk] + 1 : 1;
        rvkExample[rvk] = rvkNode;
    }
    var rvkSorted = Object.keys(frequencies);
    rvkSorted.sort();
    $("#rvkaggregiert").html("");
    var parent;
    for (let rvk of rvkSorted) {
         
         let jsonstring = $(rvkExample[rvk]).attr("data-json");
         if (jsonstring) {
             let jsondata = JSON.parse(jsonstring);
             let treeconcepts = [];
             let treenotations = [];
             let currentjson = jsondata;
             while (currentjson) {
                 if ("node" in currentjson) {
                     if ("benennung" in currentjson.node) {
                         treeconcepts.unshift(currentjson.node.benennung);
                         treenotations.unshift(currentjson.node.notation);
                     }
                     if ("ancestor" in currentjson.node) {
                         currentjson = currentjson.node.ancestor;
                     } else {
                         currentjson = null;
                     }
                 } else {
                     currentjson = null;
                 }
             }
             parent = $("#rvkaggregiert");
             for (let i=0; i<treeconcepts.length; i++) {
                 let node = treeconcepts[i];
                 let notation = treenotations[i];
                 let check = $("#rvkaggregiert *[title='" + notation + "']");
                 if (check.length == 0) {
                     let inner = $("<li>").attr("title", notation).text(node);
                     let line = $("<ul>").append(inner);
                     parent.append(line);
                 }
                 parent = $("#rvkaggregiert *[title='" + notation + "']");
             }
         }
         if (rvkExample[rvk].classList.contains("rvkError")) {
             parent = $("#rvkaggregiert");
             parent.append("<br/>[Error]<br/>");
         } else {
            if (!parent) {
                parent = $("#rvkaggregiert");
            }
              parent.append(": ");
         }
         parent.append("<b>" + frequencies[rvk] + " x </b>");
         $(rvkExample[rvk]).clone().appendTo(parent);
    }
    $("#rvkaggregiert").append("<br/><br/><small><img src='../img/flash.svg' height='15px' /> powered by <a href='https://rvk.uni-regensburg.de/api/'>RVK API</a></small>");
}

function renderDDC(ddcArray)
{
    if (typeof ddcArray === 'string') {
        return ddcArray;
    } else {
        for (var i=0; i<ddcArray.length; i++) {
            var ddc = ddcArray[i];
            //var ddcUrl = 'http://dewey.info/class/' + ddc + '/';
            //var ddcUrl = 'https://deweysearchde.pansoft.de/webdeweysearch/executeSearch.html?lastScheduleRecord=' + ddc + '&lastTableRecord=&query=' + ddc;
            var ddcUrl = 'https://coli-conc.gbv.de/cocoda/app/?fromScheme=http%3A%2F%2Fdewey.info%2Fscheme%2Fedition%2Fe23%2F&toScheme=http%3A%2F%2Furi.gbv.de%2Fterminology%2Frvk%2F&from=http%3A%2F%2Fdewey.info%2Fclass%2F' + ddc + '%2Fe23%2F';
            ddcArray[i] = '<a href="' + ddcUrl + '" target="_blank">' + ddc + '</a>';
        }
        return ddcArray.join(', ');
    }
}


// Alle speziellen Links mit der ausgewählten Bibliothek und Verbund
// als URL-Paramter updaten.
function updateLinks() {
    let bibliothek = getParameterByName("bibliothek");
    let verbund = getParameterByName("verbund");
    parameters = []
    if (bibliothek != "") {
        parameters.push("bibliothek=" + bibliothek);
    }
    if (verbund != "") {
        parameters.push("verbund=" + verbund);
    }
    if (parameters.length > 0) {
        for (let linkNode of document.getElementsByClassName("link")) {
            let separator = "?";
            if (linkNode.dataset.href.includes("?")) {
                separator = "&";
            }
            linkNode.href = linkNode.dataset.href + separator + parameters.join("&")
        }
    }
}


function renderRelationen(relationenArray)
{
    if (typeof relationenArray === 'string') {
        return relationenArray;
    } else {
        for (var i=0; i<relationenArray.length; i++) {
            var rel = relationenArray[i];
            relationenArray[i] = '<a class="link" href="./suche.html?isbn=' + rel + '" data-href="./suche.html?isbn=' + rel + '" target="_blank">' + rel + '</a>';
        }
        return relationenArray.join(', ');
    }
}


var overallPaketSigel = [];//alle bereits gefunden Sigel werden in einer Liste gespeichert

function renderPS(psArray)
{
// PS = Produktsigel
    if (typeof psArray === 'string') {
        return psArray;
    } else {
        var outputArray = [];
        for (var i=0; i<psArray.length; i++) {
            var ps = psArray[i];
            if (overallPaketSigel[ps]) {//um Doppelungen zu vermeiden
                continue;
            }
            overallPaketSigel[ps] = true;
            outputArray.push('<span title="" style="background-color:#ffffff">' + ps + '</span>');
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

function renderLinks(linkArray)
{
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

function renderBestand(bestandArray, id, verbund)
{
    var bibArray = $.map(bestandArray, function (sigel) {
        if (sigel === "180" && verbund == "k10plus") {
            return '<span style="border:2px solid red">180</span>';
        } else {
            return sigel;
        }
    });
    if (bibArray.length > 0) {
        return "Insgesamt "+bibArray.length+" Bibliotheken im " + verbund.toUpperCase() + " mit Bestand: "+bibArray.join(", ");
    }
    return "";
}


//Einfache Ersetzung von einigen speziellen Zeichen
//zur Darstellung als Text in HTML. Insbesondere bei
//Homonymzusätzen.
function htmlEscape(str)
{
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}


function renderSW(swObject)
{
    var swArray = [];
    var swCopyText = [];
    $.each(swObject, function (key, value) {
        swCopyText.push(key);
        if (typeof value == 'string') {
            var swUrl = 'https://d-nb.info/gnd/' + value + '/about/html';
            swArray.push('<a href="' + swUrl + '" target="_blank">' + htmlEscape(key) + '</a>');
            swCopyText.push(value);
        } else {
            swArray.push(key);
        }
    });
    if (swArray.length > 0) {
        var swCopyButton = $("<button>")
            .addClass("btn")
            .attr("title", "Schlagwörter kopieren")
            .attr("data-clipboard-text", swCopyText.join('\n'))
            .append("<img src='../img/clippy.svg' width='16'/>");
        return swArray.join('; ') + "&emsp;" + swCopyButton.prop('outerHTML');
    }
    return "";
}

function bestellInfo(databaseText, currentRecord)
{
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

function renderTitle(data)
{
    var info = "<b>"+data["titel"][0] + "</b> <i>" + data["autor"][0] + "</i><br/>";
    if (data["gesamttitel"].length>0) {
        info += "("+data["gesamttitel"]+")<br/>";
    }
    if (data["hochschulvermerk"].length>0) {
        info += data["hochschulvermerk"]+"<br/>";
    }
    if (data["isbn"].length>0) {
        info += data["isbn"].join(", ")+"<br/>";
    }
    return info;
}

function coins(currentRecord)
{
    // rudimentärer COinS Daten um beispielsweise Titel in Citavi, Zotero oder Mendeley zu speichern
    // und darüber Bestellungen zu verwalten:
    var coins = '<span class="Z3988" title="url_ver=Z39.88-2004&amp;ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';
    if (currentRecord.titel) {
        coins += '&amp;rft.btitle=' + encodeURIComponent(currentRecord.titel); }
    if (currentRecord.erscheinungsinfo) {
        coins += '&amp;rft.publisher=' + encodeURIComponent(currentRecord.erscheinungsinfo[1]); }
    if (currentRecord.erscheinungsinfo) {
        coins += '&amp;rft.isbn=' + encodeURIComponent(currentRecord.isbn); }
    if (currentRecord.auflage) {
        coins += '&amp;rft.edition=' + encodeURIComponent(currentRecord.auflage); }
    if (currentRecord.autor) {
        coins += '&amp;rft.au=' + encodeURIComponent(currentRecord.autor); }
    if (currentRecord.erscheinungsinfo) {
        coins += '&amp;rft.date=' + encodeURIComponent(currentRecord.erscheinungsinfo[2]); }
    coins += '"></span>';

    return coins;
}
 

function getParameterByName(name)
{
/*
 * Parameter als URL-Anhaenge werden direkt
 * ueber ihren Namen ansprechbar. Z.B.
 * bei liste-se.html?isbn=0521518148
 * getParameterByName("isbn") = 0521518148
 */
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

 
function isbn10(z)
{
/*
 * Umwandlung in eine 10-stellige ISBN,
 * wobei die Eingabe entweder bereits
 * eine 10-stellige ISBN oder eine 13-
 * stellige ISBN sein muss.
 */
    if (z.length === 13) {
        d = z.split('').map(Number);
        var t = (d[3] + 2 * d[4] + 3 * d[5] + 4 * d[6] + 5 * d[7] + 6 * d[8] + 7 * d[9] + 8 * d[10] + 9 * d[11] ) % 11;
        if (t === 10) {
            t = 'X';
        }
        return z.substr(3, 9) + t;
    } else {
        return z;
    }
}

function isbn13(z)
{
/*
 * Umwandlung in eine 13-stellige ISBN,
 * wobei die Eingabe entweder bereits
 * eine 13-stellige ISBN oder eine 10-
 * stellige ISBN sein muss.
 */
    z = z.replace(/-/g, "").replace(/ /g, "").replace(/x/g, "X");
    if (z.length === 10) {
        z = '978' + z.substr(0, 9);
        d = z.split('').map(Number);
        var t = (10 - ((d[0] + 3*d[1] + d[2] + 3*d[3] + d[4] + 3*d[5] + d[6] + 3*d[7] + d[8] + 3*d[9] + d[10] + 3*d[11] ) % 10 )) % 10;
        return z + t;
    } else {
        return z;
    }
}
