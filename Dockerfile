# Run malibu in a container
# https://github.com/UB-Mannheim/malibu/
# 
# USAGE:
# $ docker run -d --rm -p <local-port>:80 --name malibu-container malibu
# 

FROM php:7.3-apache-buster

ENV JQUERY 3.6.0
ENV CLIPBOARD 2.0.8

RUN apt-get update \
    && apt-get install --no-install-recommends -y yaz libyaz-dev wget unzip python3-bs4 python3-requests \
    && pecl install yaz \
    && docker-php-ext-enable yaz \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/malibu

# create minimal set of directories and files needed for retrieving external files
RUN  mkdir isbn bnb
COPY bnb/getBNBData bnb/getBNBData

# From the best practices: you should use curl or wget instead of ADD
# https://docs.docker.com/engine/userguide/eng-image/dockerfile_best-practices/#add-or-copy
RUN curl -o "isbn/jquery.min.js" "https://cdnjs.cloudflare.com/ajax/libs/jquery/${JQUERY}/jquery.min.js"
RUN curl -o "isbn/clipboard.min.js" "https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/${CLIPBOARD}/clipboard.min.js"

# Download BNB data
RUN chmod +x bnb/getBNBData && bnb/getBNBData "$PWD/bnb/BNBDaten"

# Copy the complete directory structure sans entries in .dockerignore
COPY . .

# Configure
RUN mv isbn/conf.example.php isbn/conf.php && \
    mv isbn/paketinfo.example.js isbn/paketinfo.js
