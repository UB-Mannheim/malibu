# Install Notes

## General Requirements

 * Sever: Apache with PHP, jQuery library
 * Client: Javascript enabled

## bnb

We included the getBNBData.sh as a cronjob, which runs every Saturday, at 10 am (010**6).

## isbn

You need the php library <a href="http://php.net/manual/en/book.yaz.php">yaz</a> on the server. For Debian 7:

1. <code>apt-get install yaz libyaz4-dev php5-dev php-pear</code> (maybe you have already some packages)
2. <code>pecl install yaz</code>
3. Open <code>/etc/php5/apache2/php.ini</code>, goto section <code>Dynamic Extensions</code> and add <code>extention=yaz.so</code>

Maybe, the file <code>yaz.so</code> is in a different directory then what is indicated in <code>extension_dir</code> in <code>/etc/alternatives/php-config</code> and you have toc copy it therefore.
(e.g. <code>cp /usr/lib/php5/20090626/yaz.so /usr/lib/php5/20100525/yaz.so</code> in our case).
