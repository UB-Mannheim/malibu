# Install Notes

The easiest way should be to use the docker
container as it is already described in the
[README.md](README.md#docker). This can be used for
testing also for production.

However, there are alternatives ways to set
it up on Debian without docker and how you can set up a
an environment for code development.


* [Server configuration](#server-configuration)
* [Configuration for code development](#configuration-for-code-development)


## Server configuration

General remark for the bnb service: We included `getBNBData.sh` as a cronjob, which runs every Saturday, at 10 am (010**6).


### For Debian 7 (Apache 2.2)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server. For Debian 7 (Apache 2.2):

1. <code>apt-get install yaz libyaz4-dev php5-dev php-pear</code> (maybe you have already some packages)
2. <code>pecl install yaz</code>
3. Open `/etc/php5/apache2/php.ini`, goto section `Dynamic Extensions` and add `extension=yaz.so`

Maybe, the file `yaz.so` is not located in the directory
which is configured by `extension_dir` in
`/etc/alternatives/php-config` and you have to copy it therefore.
(e.g. <code>cp /usr/lib/php5/20090626/yaz.so /usr/lib/php5/20100525/yaz.so</code> in our case).

### For Debian 8 (Apache 2.4)
You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server. For Debian 8 (Apache 2.4):

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


## Configurations for code development

## Dev Server

The `./dist/dev-server.sh` script will install the dependencies of malibu system-wide and start
a server on port `8090`. The purpose is to create a working development
environment on a Debian/Ubuntu system in a single step.

To run the dev server:

```
bash ./dist/dev-server.sh
```

## Docker

To rebuild the image or run a container with the current development version:

```
make -C dist docker
make -C dist docker-run
```

