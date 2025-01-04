<?php
/*
 * Source: https://github.com/UB-Mannheim/malibu/
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
 * hbz?isbn=ISBN
 *   ISBN ist eine 10- bzw. 13-stellige ISBN mit/ohne Bindestriche/Leerzeichen
 *   ISBN kann ebenfalls eine Komma-separierte Liste von ISBNs sein
 * hbz?ppn=PPN
 *   PPN ist die eine ID-Nummer des HBZ
 * hbz?isbn=ISBN&format=json
 * hbz?ppn=PPN&format=json
 *   Ausgabe erfolgt als JSON
 *
 * Sucht übergebene ISBN bzw. PPN im HBZ-Katalog
 * und gibt maximal 10 Ergebnisse als MABXML zurück
 * bzw. als JSON.
 *
 * SRU-Schnittstelle vom HBZ beschrieben unter:
 * https://service-wiki.hbz-nrw.de/display/VDBE/Zugriff+auf+die+hbz-Verbunddatenbank+via+SRU
 *
 */


$url = str_replace('/hbz.php', '/alma-sru.php', $_SERVER['REQUEST_URI']) . "&bibliothek=DE-HBZ";
header('Location: '. $url);
