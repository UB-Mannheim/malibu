# Run malibu in a container
# https://github.com/UB-Mannheim/malibu/
# 
# USAGE:
# $ docker run -d --rm --port <local-port>:80 --name malibu-container malibu
# 

FROM php:apache

ENV JQUERY 3.2.1
ENV CLIPBOARD 1.7.1

RUN apt-get update && apt-get install -y yaz libyaz4-dev php-dev php-pear wget unzip

RUN pecl install yaz
RUN docker-php-ext-enable yaz

RUN mkdir malibu
WORKDIR malibu
RUN mkdir isbn

# From the best practices: you should use curl or wget instead of ADD
# https://docs.docker.com/engine/userguide/eng-image/dockerfile_best-practices/#add-or-copy
RUN curl -o "isbn/jquery-${JQUERY}.min.js" "https://code.jquery.com/jquery-${JQUERY}.min.js"
RUN curl -o "isbn/clipboard.min.js" "https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/${CLIPBOARD}/clipboard.min.js"

# Download BNB data
COPY ./bnb/getBNBData.sh ./bnb/getBNBData.sh
RUN bash ./bnb/getBNBData.sh $PWD/bnb/BNBDaten

# Copy the complete directory structure sans entries in .dockerignore
COPY . .

# Configure
RUN mv isbn/conf.example.php isbn/conf.php && \
    mv isbn/paketinfo.example.js isbn/paketinfo.js
