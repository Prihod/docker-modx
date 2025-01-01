#!/bin/bash
set -e

mkdir -p "$SSL_PATH"

echo "Start generate SSL into ${SSL_PATH}"
openssl req -x509 -nodes -days 365 \
      -newkey rsa:2048 \
      -keyout "$SSL_PATH"/server.key \
      -out "$SSL_PATH"/server.crt \
      -subj "/CN=${SSL_HOST:-$MODX_HTTP_HOST}";