# Run malibu in a container
# https://github.com/UB-Mannheim/malibu/
# 
# USAGE:
# $ docker run -d --rm -p <local-port>:80 --name malibu-container malibu
# 

FROM docker.io/php:8.2-apache-bullseye

ENV JQUERY 3.6.4
ENV CLIPBOARD 2.0.11

RUN apt-get update \
    && apt-get install --no-install-recommends -y yaz libyaz-dev wget unzip \
    && pecl install yaz \
    && docker-php-ext-enable yaz \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/malibu

# create minimal set of directories and files needed for retrieving external files
RUN  mkdir isbn bnb
COPY bnb/getBNBData.php bnb/getBNBData.php

# From the best practices: you should use curl or wget instead of ADD
# https://docs.docker.com/engine/userguide/eng-image/dockerfile_best-practices/#add-or-copy
RUN curl -o "isbn/jquery.min.js" "https://cdnjs.cloudflare.com/ajax/libs/jquery/${JQUERY}/jquery.min.js"
RUN curl -o "isbn/clipboard.min.js" "https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/${CLIPBOARD}/clipboard.min.js"

# Download BNB data. It's okay if at least one RDF file was downloaded.
RUN php bnb/getBNBData.php "$PWD/bnb/BNBDaten" || test -f $(ls bnb/BNBDaten/*.rdf | head -1)

# Copy the complete directory structure sans entries in .dockerignore
COPY . .

# Configure
RUN mv isbn/conf.example.php isbn/conf.php && \
    mv isbn/paketinfo.example.js isbn/paketinfo.js
