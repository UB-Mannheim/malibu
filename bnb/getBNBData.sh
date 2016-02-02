#!/bin/bash

BNB_DATADIR="/var/www/Tools/BNBDaten"
mkdir -pv "$BNB_DATADIR"
cd "$BNBDaten"

wget -r -np -l 1 -A zip "http://www.bl.uk/bibliographic/bnbrdfxml.html"

for f in $BNB_DATADIR/www.bl.uk/bibliographic/bnbrdf/*.zip
do
    unzip -o "$f"
    rm "$f"
done
