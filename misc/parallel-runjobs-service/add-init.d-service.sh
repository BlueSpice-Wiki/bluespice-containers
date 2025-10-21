#!/bin/bash

# Remove legacy
rm -rf /etc/init.d/farm-runjobs
rm -rf /etc/init.d/wiki-runjobs
cp wiki-runjobs /etc/init.d/wiki-runjobs
cp wiki-runjobs.service /etc/systemd/system/wiki-runjobs.service
sed -i -e "s|<COMMAND>|\"$(pwd)/build/parallel-runjobs-service -c $(pwd)/config.yaml\"|g" /etc/init.d/wiki-runjobs
chmod +x /etc/init.d/wiki-runjobs
sudo systemctl daemon-reload
sudo systemctl enable wiki-runjobs.service
echo "runjobs service added"
