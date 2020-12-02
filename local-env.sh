#!/bin/bash

function up() {
    docker-compose up -d
    docker exec city-expert-websocket-server composer install
    printf "Done\n"
}

case $1 in
  up)
    up
    ;;
  down)
    docker-compose down
    ;;
  *)
    printf "Unknown command\n"
    ;;
esac
