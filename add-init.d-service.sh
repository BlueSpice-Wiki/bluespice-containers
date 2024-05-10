#!/bin/bash

rm -rf /etc/init.d/farm-runjobs
composer update
cp farm-runjobs /etc/init.d/farm-runjobs
sed -i -e "s|<COMMAND>|\"$(pwd)/bin/run.php -c $(pwd)/config.yaml\"|g" /etc/init.d/farm-runjobs
chmod +x /etc/init.d/farm-runjobs
echo "runjobs service added"
