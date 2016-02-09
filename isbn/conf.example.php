<?php
/*
 * Example Configuration File for malibu
 *
 * Some services requires authentication with username and password.
 * Look up the information or ask for an individual login
 * and fill out the details here. Rename this file to conf.php.
 * @codingStandardsIgnoreStart
 *
 */


//SWB = Südwestdeutscher Bibliotheksverbund
//https://wiki.bsz-bw.de/doku.php?id=v-team:daten:z39.50neu
define('SWB_URL',           'z3950n.bsz-bw.de:20210/swb');
define('SWB_SYNTAX',        'marc21');
define('SWB_ELEMENTSET',    'xf');//Für die Ausgabe von Lokal- und Exemplardaten muss das Kommando „elementset xf“ abgesetzt werden. Bisher genügte ein „elementset f“.


//B3KAT = gemeinsamer Katalogisierungsplattform der Bibliotheksverbünde BVB und KOBV
//(BVB = Bibliotheksverbund Bayern, KOBV = Kooperativer Bibliotheksverbund Berlin-Brandenburg)
//http://www.bib-bvb.de/web/b3kat/z39.50
define('B3KAT_URL',         'bvbr.bib-bvb.de:9991/BVB01');
define('B3KAT_USER',        '');//TODO look up
define('B3KAT_PASSWORD',    '');//TODO look up
define('B3KAT_SYNTAX',      'mab2');
define('B3KAT_ELEMENTSET',  'F');// Achtung: muss gross geschrieben werden!


//HBZ = Hochschulbibliothekszentrum des Landes Nordrhein-Westfalen
//http://www.hbz-nrw.de/angebote/verbunddatenbank/verbundsystem/z3950
define('HBZ_URL',           '193.30.112.135:9991/HBZ01');
define('HBZ_SYNTAX',        '1.2.840.10003.5.16');// MAB2 = 1.2.840.10003.5.16
define('HBZ_ELEMENTSET',    'F');


//GBV = Gemeinsamer Bibliotheksverbund
//http://www.gbv.de/wikis/cls/Z39.50
define('GBV_URL',           'sru.gbv.de/gvk');
define('GBV_SYNTAX',        'marc21');
define('GBV_ELEMENTSET',    'f');


//HEBIS = Hessisches BibliotheksInformationsSystem
//http://www.hebis.de/de/1service/z3950/z39_zugang_index.php
define('HEBIS_URL',         'tolk.hebis.de:20211/hebis');
define('HEBIS_USER',        '3950');
define('HEBIS_PASSWORD',    '');//TODO look up
define('HEBIS_SYNTAX',      '');//TODO look up
define('HEBIS_ELEMENTSET',  'F');


//NEBIS
//http://nebis2.ethz.ch/technik/z3950.html
define('NEBIS_URL',           'opac.nebis.ch:9909/NEBIS_UTF');
define('NEBIS_SYNTAX',        'marc21');
define('NEBIS_ELEMENTSET',    'F');//gross


//BL = British Library
//http://www.bl.uk/bibliographic/z3950configuration.html
define('BL_URL',            'z3950cat.bl.uk:9909/BNB03U');
define('BL_USER',           '');//TODO request one
define('BL_PASSWORD',       '');//TODO request one
define('BL_SYNTAX',         'marc21');
define('BL_ELEMENTSET',     'F');


//OBVSG = Verbundkatalog des Österreichischen Bibliotheksverbundes
//https://www.obvsg.at/services/verbundsystem/z3950/
define('OBVSG_URL',         'z3950.obvsg.at:9991/ACC01');
define('OBVSG_USER',        '');//TODO request one
define('OBVSG_PASSWORD',    '');//TODO request one
define('OBVSG_SYNTAX',      'mab2');
define('OBVSG_ELEMENTSET',  'F');

//you can add more here
