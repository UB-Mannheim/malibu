# Run malibu in a container
# https://github.com/UB-Mannheim/malibu/
# 
# USAGE:
# $ docker run -d --rm --port <local-port>:80 --name malibu-container malibu
# 

FROM php:apache

RUN apt-get update && apt-get install -y yaz libyaz4-dev php5-dev php-pear wget unzip

RUN pecl install yaz
RUN docker-php-ext-enable yaz

RUN mkdir malibu
WORKDIR malibu

# Download the jQuery file with curl
# because wget is not installed on php:apache
ADD "https://code.jquery.com/jquery-2.1.1.min.js" "isbn/jquery-2.1.1.min.js"

# Download BNB data
COPY ./bnb/getBNBData.sh ./bnb/getBNBData.sh
RUN bash ./bnb/getBNBData.sh $PWD/bnb/BNBDaten

# Copy the complete directory structure sans entries in .dockerignore
COPY . .

# Configure
RUN mv isbn/conf.example.php isbn/conf.php
RUN mv isbn/paketinfo.example.js isbn/paketinfo.js
