FROM php:apache

RUN apt-get update && apt-get install -y yaz libyaz4-dev php5-dev php-pear

RUN pecl install yaz
RUN docker-php-ext-enable yaz

COPY isbn/conf.example.php isbn/conf.php
COPY isbn/paketinfo.example.js isbn/paketinfo.js
RUN curl -o "isbn/jquery-2.1.1.min.js" "https://code.jquery.com/jquery-2.1.1.min.js"

COPY . /var/www/html/
