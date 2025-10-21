#!/bin/bash

PHP_BIN=/usr/local/bin/php
SERVER_SCRIPT=./bin/server.php
PID_FILE=/tmp/server.pid

start_server() {
  # Log output to stdout
	$PHP_BIN $SERVER_SCRIPT &
	echo $! > $PID_FILE
}

is_server_running() {
  if [ -f $PID_FILE ]; then
    PID=`cat $PID_FILE`
    if ps -p $PID > /dev/null; then
      return 0
    else
      return 1
    fi
  else
    return 1
  fi
}

restart_server() {
  printf "Server is not running, restarting...\n"
  start_server
}

monitor_server() {
	while true; do
		is_server_running
		if [ $? -eq 0 ]; then
			sleep 1
		else
			restart_server
		fi
	done
}

# Start the server
printf "Starting the server...\n"
start_server
# Monitor the server
monitor_server
