<!DOCTYPE html>
<html>
<!--
Source: https://github.com/UB-Mannheim/malibu/

Copyright (C) 2013 Universitätsbibliothek Mannheim

Author:
   Philipp Zumstein <philipp.zumstein@bib.uni-mannheim.de>

This is free software licensed under the terms of the GNU GPL,
version 3, or (at your option) any later version.
See <http://www.gnu.org/licenses/> for more details.

Beschreibung: Diese Web-Formular ermöglicht die gewünschten DDC-Stellen
auszuwählen und einige Filtermöglichkeiten einzustellen sowie Datenlieferungen
der heruntergeladenen BNB weekly im RDF-Format auszuwählen.
Beim Abschicken des Resultates wird das Skript bnb.php mit den
entsprechenden Parametern aufgerufen.

Direktaufruf: Es ist ebenfalls möglich die DDC-Stellen bereits beim
Aufruf direkt auszuwählen und somit beispielsweise ein bequemes
Lesezeichen zu erstellen, z.B.
   recherche.php?ddcgruppe[]=004%2C005%2C006

-->
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style type="text/css">
            body { font-family:  Arial, Verdana, sans-serif; }
        </style>
        <title>BNB Recherche</title>
    </head>
    <body>

        <a href="https://github.com/UB-Mannheim/malibu"><img style="position: absolute; top: 0; right: 0; border: 0;" src="../img/fork-github.png" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>

        <h1>BNB Recherche</h1>

        Zum automatischen
        Bestandsabgleich bei den ISBNs kann das
        <a href="http://wiki.bib.uni-mannheim.de/InterneSeiten/doku.php?id=anleitungen:utfa">Autolink Tool (Unterstützungstool für Fachreferatsarbeit)</a>
        genutzt werden (auf der Seite Greasemonkey
        aktivieren <img src="../img/Autolink.jpg" >).

        <h3>Schritt 1: DDC Auswählen</h3>
        Entweder können Sie die DDC anhand der Sachgruppen der DNB auswählen,
        oder eine Reihe von DDC Stellen direkt in das Formularfeld unten eingeben.
        Die
        <a href="http://www.dnb.de/SharedDocs/Downloads/DE/DNB/service/ddcSachgruppenDNB.pdf">Sachgruppen der DNB</a>
        wurden jeweils großzügig ausgelegt, so dass
        einige Klassen sich überschneiden (dies wird jeweils mit den []-Klammern
        explizit erwähnt).
        (Lesezeichen testen).

        <form action="">
            <p>
                <select name="ddcgruppe[]" size="10" multiple>
                    <optgroup label="000 Allgemeines, Informatik, Informationswissenschaften">
                        <option value="000,001,002,003">000 Allgemeines, Wissenschaft</option>
                        <option value="004,005,006">004 Informatik</option>
                        <option value="01">010 Bibliografien</option>
                        <option value="02">020 Bibliotheks- und Informationswissenschaft</option>
                        <option value="03">030 Enzyklopädien</option>
                        <option value="05">050 Zeitschriften, fortlaufende Sammelwerke</option>
                        <option value="06">060 Organisationen, Museumswissenschaft</option>
                        <option value="07">070 Nachrichtenmedien, Journalismus, Verlagswesen</option>
                        <option value="08">080 Allgemeine Sammelwerke</option>
                        <option value="09">090 Handschriften, seltene Bücher</option>
                    </optgroup>
                    <optgroup label="100 Philosophie und Psychologie">
                        <option value="10,11,12,14,16,17,18,19">100 Philosophie</option>
                        <option value="13">130 Parapsychologie, Okkultismus</option>
                        <option value="15">150 Psychologie</option>
                    </optgroup>
                    <optgroup label="200 Religion">
                        <option value="20,21">200 Religion, Religionsphilosophie</option>
                        <option value="22">220 Bibel</option>
                        <option value="23,24,25,26,27,28">230 Theologie, Christentum</option>
                        <option value="29">290 Andere Religionen</option>
                    </optgroup>
                    <optgroup label="300 Sozialwissenschaften">
                        <option value="30">300 Sozialwissenschaften, Soziologie, Anthropologie</option>
                        <option value="31">310 Allgemeine Statistiken</option>
                        <option value="32">320 Politik</option>
                        <option value="33">330 Wirtschaft (Management in 650) [inkl. 333.7]</option>
                        <option value="333.7,333.8,333.9">333.7 Natürliche Ressourcen, Energie und Umwelt</option>
                        <option value="34">340 Recht (Kriminologie, Strafvollzug in 360)</option>
                        <option value="350,351,352,353,354">350 Öffentliche Verwaltung</option>
                        <option value="355,356,357,358,359">355 Militär</option>
                        <option value="36">360 Soziale Probleme, Sozialdienste, Versicherungen</option>
                        <option value="37">370 Erziehung, Schul- und Bildungswesen</option>
                        <option value="38">380 Handel, Kommunikation, Verkehr (Philatelie in 760)</option>
                        <option value="39">390 Bräuche, Etikette, Folklore</option>
                    </optgroup>
                    <optgroup label="400 Sprache">
                        <option value="40,41">400 Sprache, Linguistik</option>
                        <option value="42">420 Englisch</option>
                        <option value="43">430 Deutsch [inkl. 439]</option>
                        <option value="439">439 Andere germanische Sprachen</option>
                        <option value="44">440 Französisch, romanische Sprachen allgemein</option>
                        <option value="45">450 Italienisch, Rumänisch, Rätoromanisch</option>
                        <option value="46">460 Spanisch, Portugiesisch</option>
                        <option value="47">470 Latein</option>
                        <option value="48">480 Griechisch</option>
                        <option value="49">490 Andere Sprachen [inkl. 491.7]</option>
                        <option value="491.7,491.8">491.7 Slawische Sprachen</option>
                    </optgroup>
                    <optgroup label="500 Naturwissenschaft und Mathematik">
                        <option value="50">500 Naturwissenschaften</option>
                        <option value="51">510 Mathematik</option>
                        <option value="52">520 Astronomie, Kartographie</option>
                        <option value="53">530 Physik</option>
                        <option value="54">540 Chemie (Biochemie in 570)</option>
                        <option value="55">550 Geowissenschaften (tw. in 520, 540)</option>
                        <option value="56">560 Paläontologie</option>
                        <option value="57">570 Biowissenschaften, Biologie</option>
                        <option value="58">580 Pflanzen (Botanik)</option>
                        <option value="59">590 Tiere (Zoologie)</option>
                    </optgroup>
                    <optgroup label="600 Technik, Medizin, angewandte Wissenschaften">
                        <option value="60">600 Technik</option>
                        <option value="61">610 Medizin, Gesundheit (Veterinärmedizin in 630)</option>
                        <option value="62,621.0,621.1,621.2,621.40,621.41,621.42,621.43,621.44,621.45,621.47,621.48,621.49,621.5,621.6,621.7,621.8,621.9,623,625.19,625.2,629.0,629.1,629.2,629.3,629.4,629.5,629.6,629.7,629.9">620 Ingenieurwissenschaften und Maschinenbau</option>
                        <option value="621.3,621.46,629.8">621.3 Elektrotechnik, Elektronik</option>
                        <option value="622,624,625.0,625.10,625.11,625.12,625.13,625.14,625.15,625.16,625.17,625.18,625.3,625.4,625.5,625.6,625.7,625.8,625.9,626,627,628">624 Ingenieurbau und Umwelttechnik</option>
                        <option value="63">630 Landwirtschaft, Veterinärmedizin</option>
                        <option value="64">640 Hauswirtschaft und Familienleben</option>
                        <option value="65">650 Management</option>
                        <option value="66">660 Technische Chemie</option>
                        <option value="67,68">670 Industrielle und handwerkliche Fertigung</option>
                        <option value="69">690 Hausbau, Bauhandwerk</option>
                    </optgroup>
                    <optgroup label="700 Künste und Unterhaltung">
                        <option value="70">700 Künste, Bildende Kunst allgemein</option>
                        <option value="71">710 Landschaftsgestaltung, Raumplanung</option>
                        <option value="72">720 Architektur</option>
                        <option value="73">730 Plastik, Numismatik, Keramik, Metallkunst</option>
                        <option value="74">740 Grafik, angewandte Kunst [inkl. 741.5]</option>
                        <option value="741.5">741.5 Comics, Cartoons, Karikaturen</option>
                        <option value="75">750 Malerei</option>
                        <option value="76">760 Druckgrafik, Drucke</option>
                        <option value="77">770 Fotografie, Video, Computerkunst</option>
                        <option value="78">780 Musik</option>
                        <option value="790">790 Freizeitgestaltung, Darstellende Kust</option>
                        <option value="791">791 Öffentliche Darbietungen, Film, Rundfunk</option>
                        <option value="792">792 Theater, Tanz</option>
                        <option value="793,794,795">793 Spiel</option>
                        <option value="796,797,798,799">796 Sport</option>
                    </optgroup>
                    <optgroup label="800 Literatur">
                        <option value="80">800 Literatur, Rhetorik, Literaturwissenschaft</option>
                        <option value="81">810 Englische Literatur Amerikas</option>
                        <option value="82">820 Englische Literatur</option>
                        <option value="83">830 Deutsche Literatur [inkl. 839]</option>
                        <option value="839">839 Literatur in anderen germanischen Sprachen</option>
                        <option value="84">840 Französische Literatur</option>
                        <option value="85">850 Italienische, rumänische, ratoromanische Literatur</option>
                        <option value="86">860 Spanische und portugiesische Literatur</option>
                        <option value="87">870 Lateinische Literatur</option>
                        <option value="88">880 Griechische Literatur</option>
                        <option value="89">890 Literatur in anderen Sprachen [inkl. 891.8]</option>
                        <option value="891.8">891.8 Slawische Literatur</option>
                    </optgroup>
                    <optgroup label="900 Geschichte und Geografie">
                        <option value="90">900 Geschichte</option>
                        <option value="91">910 Geografie, Reisen [inkl. 914.3]</option>
                        <option value="914.3">914.3 Geografie, Reisen (Deutschland)</option>
                        <option value="92">920 Biografie, Genealogie, Heraldik</option>
                        <option value="93">930 Alte Geschichte, Archäologie</option>
                        <option value="94">940 Geschichte Europas [inkl. 943]</option>
                        <option value="943">943 Geschichte Deutschlands</option>
                        <option value="95">950 Geschichte Asiens</option>
                        <option value="96">960 Geschichte Afrikas</option>
                        <option value="97">970 Geschichte Nordamerikas</option>
                        <option value="98">980 Geschichte Südamerikas</option>
                        <option value="99">990 Geschichte der übrigen Welt</option>
                    </optgroup>

                </select>
                <input type="submit" value="DDC-Stellen auswählen">
                <input type="button" name="cancel" value="Zurücksetzen" onclick="window.open('recherche.php')">
            </p>
        </form>
        <form action="bnb.php">
            DDC-Stellen eingeben oder eingeben lassen (automatische Rechtstrunkierung, mehrere Stellen durch Komma trennen)<br/>
            <?php
            //var_dump($_GET["ddcgruppe"]);
            if (isset($_GET["ddcgruppe"])) {
                $ddcListe = implode(",", $_GET["ddcgruppe"]);
                echo '<input name="ddc" type="text" size="60" maxlength="60" value=' . $ddcListe . '><br/>';
            } else {
                echo '<input name="ddc" type="text" size="60" maxlength="60"><br/>';
            }
            ?>
            <h3>Schritt 2: Filter einstellen</h3>
            <table>
                <tr>
                    <td>"Forthcoming publication" auch anzeigen?</td>
                    <td><input type="radio" name="forth" value="yes">ja
                        <input type="radio" name="forth" value="no" checked>nein
                        <input type="radio" name="forth" value="only">nur diese</td>
                </tr>
                <tr>
                <td>Publikationen ohne ISBN (Zeitschriften, graue Literatur) auch anzeigen?</td>
                <td><input type="radio" name="noisbn" value="yes">ja
                    <input type="radio" name="noisbn" value="no" checked>nein
                    <input type="radio" name="noisbn" value="only">nur diese</td>
                </tr>
            </table>
            <h3>Schritt 3: Datenlieferungen auswählen</h3>
            Welche Bände sollen durchgesehen werden? Bitte auswählen<br/>
            <?php
            $mapDate = array(3242 => "08/05/2013", 3241 => "01/05/2013", 3240 => "24/04/2013", 3239 => "17/04/2013", 3238 => "10/04/2013", 3237 => "03/04/2013", 3236 => "20/03/2013", 3231 => "13/02/2013");

            $files = glob('BNBDaten/*.rdf');
            usort($files, function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });
            echo "<table>";
            foreach ($files as $filename) {// BNBDaten/bnbrdf_N3240.xml
                $filenameShort = substr($filename, 9);
                $nr = substr($filenameShort, -8, -4);
                echo "<tr>";
                echo "<td><input type=\"checkbox\" name=\"nr[]\" value=\"$nr\"></td><td width=\"150\">$filenameShort</td><td>";
                // @codingStandardsIgnoreStart
                if (isset($mapDate[$nr])) {
                    echo $mapDate[$nr];
                } else {
                    echo date("d/m/Y", filemtime($filename));
                }
                // @codingStandardsIgnoreEnd
                echo "</td></tr>\n";
            }
            echo "</table>";


            ?>
            <h3>Recherche starten:
                <input type="submit" value="START" style="height: 40px"></h3>
                Alphabetische Sortierung nach Verlag
        </form>
    </body>
</html>
