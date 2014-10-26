#!/bin/bash

cd /var/www/Tools/BNBDaten

wget -r -np -l 1 -A zip http://www.bl.uk/bibliographic/bnbrdfxml.html

for f in /var/www/Tools/BNBDaten/www.bl.uk/bibliographic/bnbrdf/*.zip
do 
unzip -o $f
rm $f
done
