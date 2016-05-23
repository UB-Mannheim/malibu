# Run malibu in a container
# https://github.com/UB-Mannheim/malibu/
# 
# USAGE:
# $ docker run -d -P --name malibu-container malibu
# 

FROM php:apache

RUN apt-get update && apt-get install -y yaz libyaz4-dev php5-dev php-pear

RUN pecl install yaz
RUN docker-php-ext-enable yaz

COPY isbn/conf.example.php isbn/conf.php
COPY isbn/paketinfo.example.js isbn/paketinfo.js

# Download the jQuery file with curl
# because wget is not installed on php:apache
RUN curl -o "isbn/jquery-2.1.1.min.js" "https://code.jquery.com/jquery-2.1.1.min.js"

# RUN  ./bnb/getBNBData.sh

COPY . /var/www/html/
