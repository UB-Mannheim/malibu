# malibu - Mannheim library utilities

[![Build Status](https://travis-ci.org/UB-Mannheim/malibu.svg?branch=master)](https://travis-ci.org/UB-Mannheim/malibu)
[![GitHub release](https://img.shields.io/github/release/UB-Mannheim/malibu.svg?maxAge=2592000)](https://github.com/UB-Mannheim/malibu/releases)
[![license](https://img.shields.io/github/license/UB-Mannheim/malibu.svg?maxAge=2592000)](https://github.com/UB-Mannheim/malibu/blob/master/LICENSE)
[![Docker Stars](https://img.shields.io/docker/pulls/ubma/malibu.svg?maxAge=2592000)](https://hub.docker.com/r/ubma/malibu/)

## Summary/Zusammenfassung

**malibu** ![de](img/flag_de.jpeg) ist eine Sammlung von leichtgewichtigen, webbasierten Tools im Umfeld
von bibliographischen Daten zur Unterstützung von Arbeitsabläufen, wie sie
häufig in Bibliotheken bei den Fachreferaten und der Erwerbung auftreten.
Hauptbestandteil ist ein Mashup zur ISBN-Suche und ein Recherchewerkzeug für die
BNB weekly sowie weitere kleine Tools.

**malibu** ![en](img/flag_en.jpeg) is a collection of lightweight web-based tools to work with bibliographic metadata from various sources on the web, aimed at supporting the workflows of subject librarians and acquisitions librarians.

The main components are

* a mashup of various library and bookseller data on books by ISBN
  ([Demo](http://data.bib.uni-mannheim.de/malibu/isbn/suche.html)) and
* a search tool for the British National Bibliography weekly updates
  ([Demo](http://data.bib.uni-mannheim.de/malibu/bnb/recherche.php)).


## Docker

The docker image is available from Dockerhub as [ubma/malibu](https://hub.docker.com/r/ubma/malibu/).

You can run it from the command line (provided that you have already [docker installed](https://docs.docker.com/engine/installation/)):

```shell
docker run --rm -p 12345:80 'ubma/malibu'
```
Then you find malibu by opening [http://localhost:12345/malibu/isbn/suche.html](http://localhost:12345/malibu/isbn/suche.html) with your browser (maybe replace localhost by the docker ip).

More informations about how to install malibu on Debian systems and setting up a development version can be found in the [INSTALL.md](INSTALL.md).


## Copyright and License

Copyright (c) 2013 – 2016 Universitätsbibliothek Mannheim

Author: [Philipp Zumstein](https://github.com/zuphilip) (UB Mannheim)

**malibu** is Free Software. You may use it under the terms of the GNU General
Public License (GPL). See [LICENSE](./LICENSE) for details.

## References

See https://github.com/UB-Mannheim/malibu/wiki/Bibliografie

## Acknowledgements

The tools are depending on some third party libraries and fonts:

* [yaz](http://www.indexdata.com/phpyaz) ([Revised BSD](http://www.indexdata.com/licences/revised-bsd))
* [jQuery](https://github.com/jquery/jquery) (MIT license)
* [clipboard.js](https://github.com/zenorocha/clipboard.js/) (MIT license)
* clippy.svg from https://github.com/github/octicons (SIL Font)

Moreover, it is useful to use it in combination with the script
* [Autolink TIB/UB](http://www.tempelb.de/autolink-tibub/)


