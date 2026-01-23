#!/usr/bin/env bash

echo "Updating dependencies..."
npm install

# Set env vars
set -a
source <(grep -v '^#' .env | sed -E 's/([^=]+)=(.*)/\1="\2"/')
set +a

./start.sh