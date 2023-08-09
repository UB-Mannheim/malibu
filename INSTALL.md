# Install Notes

The easiest way should be to use the docker
container as it is already described in the
[README.md](README.md#docker). This can be used for
testing also for production. However, there are alternatives ways to set
it up on Debian without docker and how you can set up a
an environment for code development.

* [Server configuration](#server-configuration)
  * [For Debian 9+ (Apache)](#for-debian-9-apache)
  * [For UBUNTU 18.04 LTS / 20.04 LTS (Apache 2.4 and PHP 7.4)](#for-ubuntu-1804-lts-apache-24-and-php-74)
* [Initializing and Costumizing](#initializing-and-costumizing)
* [Configuration for code development](#configurations-for-code-development)
  * [Docker](#docker)
  * [Dev Server](#dev-server)
  * [Makefile](#makefile)

## Server configuration

### For Debian 9+ (Apache)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server.

* <code>apt-get install yaz libyaz-dev php-dev php-pear libapache2-mod-php</code> (maybe you already have some packages)
* <code>pecl install yaz</code>
* <code>PHPVERSION=$(dpkg -s libapache2-mod-php | grep -e '^Depends:' | grep -o '[0-9.]\\+$')</code>
* create new file `/etc/php/${PHPVERSION}/mods-available/yaz.ini` and add
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

## Initializing and Customizing

Some steps have to be performed after the server configuration and
before the first start:

1. Clone the repository or download all files
2. Download [jQuery](https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js) and [clipboard.js](https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js) into `isbn` directory
3. Copy `isbn/conf.example.php` to`isbn/conf.php` and customize the values
4. Copy `isbn/paketinfo.example.js` to `isbn/paketinfo.js` and customize the values

Moreover, for the retrieval of data from the British National Library, you should set up CRON to run the script `bnb/getBNBData` regularly:
```sh
# Update British National Library RDF files for malibu
0 10 * * 6 php /var/www/html/malibu/bnb/getBNBData.php /var/www/html/malibu/bnb/BNBDaten
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
