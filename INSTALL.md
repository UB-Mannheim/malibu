# Install Notes

The easiest way should be to use the docker
container as it is already described in the
[README.md](README.md#docker). This can be used for
testing also for production. However, there are alternatives ways to set
it up on Debian without docker and how you can set up a
an environment for code development.

* [Server configuration](#server-configuration)
  * [For Debian 7 (Apache 2.2)](#for-debian-7-apache-22)
  * [For Debian 8 (Apache 2.4)](#for-debian-8-apache-24)
  * [For Debian 9 (Apache 2.4 and PHP 7.0)](#for-debian-9-apache-24-and-php-70)
  * [For Debian 10 (Apache 2.4 and PHP 7.3)](#for-debian-10-apache-24-and-php-73)
  * [For Debian 11 (Apache 2.4 and PHP 7.4)](#for-debian-11-apache-24-and-php-74)
  * [For UBUNTU 18.04 LTS / 20.04 LTS (Apache 2.4 and PHP 7.4)](#for-ubuntu-1804-lts-apache-24-and-php-74)
* [Initializing and Costumizing](#initializing-and-costumizing)
* [Configuration for code development](#configurations-for-code-development)
  * [Docker](#docker)
  * [Dev Server](#dev-server)
  * [Makefile](#makefile)

## Server configuration

### For Debian 7 (Apache 2.2)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

1. <code>apt-get install yaz libyaz4-dev php5-dev php-pear</code> (maybe you have already some packages)
2. <code>pecl install yaz</code>
3. Open `/etc/php5/apache2/php.ini`, goto section `Dynamic Extensions` and add `extension=yaz.so`

Maybe, the file `yaz.so` is not located in the directory
which is configured by `extension_dir` in
`/etc/alternatives/php-config` and you have to copy it therefore.
(e.g. <code>cp /usr/lib/php5/20090626/yaz.so /usr/lib/php5/20100525/yaz.so</code> in our case).

### For Debian 8 (Apache 2.4)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

* <code>apt-get install yaz libyaz4-dev php5-dev php-pear</code> (maybe you have already some packages)
* <code>pecl install yaz</code>
* create new file `/etc/php5/mods-available/yaz.ini` and add
```sh
; configuration for php YAZ module
; priority=20
extension=yaz.so
```
* create a symbolic link
```sh
cd /etc/php5/apache2/conf.d
ln -s ../../mods-available/yaz.ini 20-yaz.ini
```
* restart Apache2
```sh
service apache2 restart
```

### For Debian 9 (Apache 2.4 and PHP 7.0)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

* <code>apt-get install yaz libyaz4-dev php-dev php-pear libapache2-mod-php</code> (maybe you already have some packages)
* <code>pecl install yaz</code>
* create new file `/etc/php/7.0/mods-available/yaz.ini` and add
```sh
; configuration for php YAZ module
; priority=20
extension=yaz.so
```
* enable the YAZ module
```sh
phpenmod yaz
```
* restart Apache2 server
```sh
systemctl restart apache2
```

### For Debian 10 (Apache 2.4 and PHP 7.3)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

* <code>apt-get install yaz libyaz-dev php-dev php-pear libapache2-mod-php</code> (maybe you already have some packages)
* <code>pecl install yaz</code>
* create new file `/etc/php/7.3/mods-available/yaz.ini` and add
```sh
; configuration for php YAZ module
; priority=20
extension=yaz.so
```
* enable the YAZ module
```sh
phpenmod yaz
```
* restart Apache2 server
```sh
systemctl restart apache2
```

### For Debian 11 (Apache 2.4 and PHP 7.4)
Basically the same as for Debian 10 BUT **yaz-config** is not available anymore, so the installation step <code>pecl install yaz</code> will fail.

You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

* <code>apt-get install yaz php-dev php-pear libapache2-mod-php7.4</code> (maybe you already have some packages)


* <code>wget -O yaz.tar.gz http://pecl.php.net/get/yaz</code>
* <code>tar -zxvf yaz.tar.gz</code>
* <code>cd yaz-<version></code>
* replace file `config.m4` with `extra/config.m4` from this repository
* <code>phpize</code>
* <code>./configure</code>
* <code>make</code>
* <code>make test</code>
* <code>make install</code>


* create new file `/etc/php/7.4/mods-available/yaz.ini` and add
```sh
; configuration for php YAZ module
; priority=20
extension=yaz.so
```
* enable the YAZ module
```sh
phpenmod yaz
```
* restart Apache2 server
```sh
systemctl restart apache2
```

### For UBUNTU 18.04 LTS / 20.04 LTS (Apache 2.4 and PHP 7.4)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

UBUNTU 18.04 LTS:

* <code>sudo apt-get install yaz libyaz5-dev php-dev php-pear libapache2-mod-php</code> (maybe you already have some packages)

UBUNTU 20.04 LTS (libyaz5-dev is no longer supported):

* <code>sudo apt-get install yaz libyaz-dev php-dev php-pear libapache2-mod-php</code> (maybe you already have some packages)

Since the php-pear package installed via the APT Package-Management is to old (1.10.9) and an upgrade "pear upgrade PEAR" will fail, you should follow these additional steps to force an upgrade to pear 1.10.11, which is essential for compiling the Yaz-Module (yaz.so) for PHP 7.4 via pecl:
```sh
sudo pear channel-update pear.php.net
sudo pecl channel-update pecl.php.net
sudo pear upgrade --force channel://pear.php.net/Archive_Tar-1.4.9 PEAR
```
Now, you can nearly follow the description for Debian 10 / PHP 7.3
* <code>sudo pecl install yaz</code>
* create new file `/etc/php/7.4/mods-available/yaz.ini` and add
```sh
; configuration for php YAZ module
; priority=20
extension=yaz.so
```
* enable the YAZ module
```sh
sudo phpenmod yaz
```
* restart Apache2 server
```sh
sudo systemctl restart apache2
```

## Initializing and Costumizing

Some steps have to be performed after the server configuration and
before the first start:

1. Clone the repository or download all files
2. Download [jQuery 3.2.1](https://code.jquery.com/jquery-3.2.1.min.js) and [clipboard.js 1.7.1](https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js) into `isbn` directory
3. Copy `isbn/conf.example.php` to`isbn/conf.php` and costumize the values
4. Copy `isbn/paketinfo.example.js` to `isbn/paketinfo.js` and costumize the values

Moreover, for the retrieval of data from the British National Library, you should set up CRON to run the script `bnb/getBNBData` regularly:
```sh
# Update British National Library RDF files for malibu
0 10 * * 6 /var/www/html/malibu/bnb/getBNBData /var/www/html/malibu/bnb/BNBDaten
```


## Configurations for code development

### Docker

The easiest way for developing/changing code on linux or windows is to use the same docker image
from Dockerhub and additionally mount the current malibu directory into docker:
```
docker run -d -p 12345:80 -v `pwd`:/var/www/html/malibu-dev ubma/malibu
```
The development version of malibu can then be accessed at [http://localhost:12345/malibu-dev/isbn/suche.html](http://localhost:12345/malibu-dev/isbn/suche.html)
(maybe you have to replace localhost with the docker ip).

### Dev Server

The `./dist/dev-server.sh` script will install the dependencies of malibu system-wide and start
a server on port `8090`. The purpose is to create a working development
environment on a Debian/Ubuntu system in a single step.

To run the dev server:

```
bash ./dist/dev-server.sh
```

### Makefile

To rebuild the image or run a container with the current development version:

```
make -C dist docker
make -C dist docker-run
```
