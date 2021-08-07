#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
JQUERY_VERSION="3.6.0"

# Use php.ini in this directory
export PHP_INI_SCAN_DIR=$DIR

# Install debian packages
APT_PACKAGES=(yaz libyaz4-dev php5-dev php-pear)
for pkg in "${APT_PACKAGES[@]}";do
    dpkg -s "$pkg" >/dev/null || sudo apt-get install "$pkg"
done

# Install PHP yaz extension
PECL_PACKAGES=(yaz)
for pkg in "${PECL_PACKAGES[@]}";do
    pecl info "$pkg" >/dev/null || sudo pecl install yaz
done

# Download jquery
if [[ ! -r "isbn/jquery.min.js" ]];then
  wget -O "isbn/jquery.min.js" "https://cdnjs.cloudflare.com/ajax/libs/jquery/${JQUERY_VERSION}/jquery.min.js"
fi

# Create conf.php
if [[ ! -r "isbn/conf.php" ]];then
    cp isbn/conf.example.php isbn/conf.php
fi

# Create paketinfo.js
if [[ ! -r "isbn/paketinfo.js" ]];then
    cp isbn/paketinfo.example.js isbn/paketinfo.js
fi

# Run the CLI server
php -S localhost:8090
