#!/bin/bash
set -e

configurator_path="${MODX_CORE_PATH}configurator"

echo "Copying files for Modx configurator..."
mkdir -p "$configurator_path"
cp -rf "${MODX_TOOLS_PATH}"/configurator/* "$configurator_path"

echo "Set permissions for Modx configurator files..."
chown -R www-data:www-data "$configurator_path"
chmod -R 775 "$configurator_path"

echo "Run composer update for Modx configurator..."
cd "$configurator_path"
composer update;

php "$configurator_path"/run.php

if [[ "$MODX_CONFIGURE_DEV_MODE" -eq 0 ]]; then
  rm -rf "$configurator_path"
  rm -rf "${MODX_CORE_PATH}cache/*"
fi

chown -R www-data:www-data "${ROOT_PATH}"
chmod -R 775 "${ROOT_PATH}"


