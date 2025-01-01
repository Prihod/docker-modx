#!/bin/bash
set -e

echo "Start export Modx"

temp_dir=$(mktemp -d)
main_config=${MODX_CORE_PATH}config/config.inc.php

if [ "$MODX_EXPORT_DB" -eq 1 ]; then
  echo "Export DB: ${MODX_DB_NAME}..."

  db_file="db.dump.sql"
  table_prefix=$(grep -Po '\$table_prefix\s*=\s*'\''.*?'\''' "$main_config" | perl -pe "s/.*'(.*?)'.*/\1/")
  table_prefix="${table_prefix:-$MODX_TABLE_PREFIX}"
  session_table="${table_prefix}session"

  echo "Cleaning table: ${session_table}..."
  mysql -u"$MODX_DB_USER" -p"$MODX_DB_PASSWORD" -h"$MODX_DB_SERVER" "$MODX_DB_NAME" -e "TRUNCATE TABLE ${session_table};"

  echo "Creating a dump: ${MODX_DB_NAME}..."
  mysqldump -u"$MODX_DB_USER" -p"$MODX_DB_PASSWORD" -h"$MODX_DB_SERVER" "$MODX_DB_NAME" > "$temp_dir"/"$db_file"
fi

if [ "$MODX_EXPORT_SITE" -eq 1 ]; then
  echo "Export site directory: ${ROOT_PATH}..."

  cd "$ROOT_PATH"
  configs=(
    "$main_config"
    "${ROOT_PATH}"/config.core.php
    "${MODX_CONTEXT_MANAGER_PATH}"config.core.php
    "${MODX_CONTEXT_CONNECTORS_PATH}"config.core.php
  )

  echo "Cleaning Modx cache folder..."
  rm -rf "${MODX_CORE_PATH}"cache/*

  if [ "$MODX_EXPORT_OVERWRITE_CONFIG" -eq 1 ]; then
    echo "Creating a backup of Modx config files..."
    for file in "${configs[@]}"
    do
      if [ -f "$file" ]; then
        echo "Backuping file: ${file}.bak"
        cp "$file" "${file}.bak"
      fi
    done

    if [ -n "$MODX_EXPORT_DB_USER" ]; then
        sed -i "s|\(\$database_user\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_DB_USER}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_DB_PASSWORD" ]; then
        sed -i "s|\(\$database_password\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_DB_PASSWORD}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_DB_SERVER" ]; then
        sed -i "s|\(\$database_server\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_DB_SERVER}';|g" "$main_config"
        sed -i "s|\(\$database_dsn = '[^:]*:host=\)[^;]*|\1${MODX_EXPORT_DB_SERVER}|" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_DB_NAME" ]; then
        sed -i "s|\(\$dbase\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_DB_NAME}';|g" "$main_config"
        sed -i "/^\$database_dsn =/s|\(dbname=\)[^;]*|\1${MODX_EXPORT_DB_NAME}|" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_CORE_PATH" ]; then
        file=${ROOT_PATH}/config.core.php
        sed -i "s|^\(define('MODX_CORE_PATH', \).*);|\1'$MODX_EXPORT_CORE_PATH');|g" "$file"

        file=${MODX_CONNECTORS_PATH}config.core.php
        sed -i "s|^\(define('MODX_CORE_PATH', \).*);|\1'$MODX_EXPORT_CORE_PATH');|g" "$file"

        file=${MODX_MANAGER_PATH}config.core.php
        sed -i "s|^\(define('MODX_CORE_PATH', \).*);|\1'$MODX_EXPORT_CORE_PATH');|g" "$file"

        sed -i "s|\(\$modx_core_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_CORE_PATH}';|g" "$main_config"
        sed -i "s|\(\$modx_processors_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_CORE_PATH}model/modx/processors/';|g" "$main_config"
     fi

    if [ -n "$MODX_EXPORT_CONNECTORS_PATH" ]; then
        sed -i "s|\(\$modx_connectors_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_CONNECTORS_PATH}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_CONNECTORS_URL" ]; then
        sed -i "s|\(\$modx_connectors_url\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_CONNECTORS_URL}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_MANAGER_PATH" ]; then
        sed -i "s|\(\$modx_manager_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_MANAGER_PATH}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_MANAGER_URL" ]; then
        sed -i "s|\(\$modx_manager_url\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_MANAGER_URL}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_HTTP_HOST" ]; then
         sed -i "s|\(\$http_host\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_HTTP_HOST}';|g" "$main_config"
        sed -i "s|$MODX_HTTP_HOST|$MODX_EXPORT_HTTP_HOST|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_BASE_PATH" ]; then
        sed -i "s|\(\$modx_base_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_BASE_PATH}';|g" "$main_config"
        sed -i "s|\(\$modx_assets_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_BASE_PATH}assets/';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_BASE_URL" ]; then
        sed -i "s|\(\$modx_base_url\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_BASE_URL}';|g" "$main_config"
        sed -i "s|\(\$modx_assets_url\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_BASE_URL}assets/';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_ASSETS_PATH" ]; then
        sed -i "s|\(\$modx_assets_path\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_ASSETS_PATH}';|g" "$main_config"
    fi

    if [ -n "$MODX_EXPORT_ASSETS_URL" ]; then
        sed -i "s|\(\$modx_assets_url\s*=\s*\)[\"'][^\"']*[\"'];|\1'${MODX_EXPORT_ASSETS_URL}';|g" "$main_config"
    fi
  fi

  archive_site="site.zip";
  echo "Compress site dir into: ${archive_site}"
  zip -q -r "$archive_site" ./ -x "./*.php.bak"

  if [ "$MODX_EXPORT_OVERWRITE_CONFIG" -eq 1 ]; then
    for file in "${configs[@]}"
      do
        file_bak="${file}.bak"
        if [ -f "$file_bak" ]; then
          mv "$file_bak" "$file"
        fi
      done
  fi

  echo "Moving ${archive_site} to ${temp_dir}"
  mv "$archive_site" "$temp_dir"
fi

if [ "$(ls -A "$temp_dir")" ]; then
  cd "$temp_dir"
  date=$(date +\%F_\%T)
  export_file="${date}.zip"

  echo "Compress all export into: ${export_file}"
  zip -r "$export_file" ./

  echo "Moving ${export_file} to ${MODX_BACKUP_PATH}"
  mv "$export_file" "$MODX_BACKUP_PATH"
fi

echo "Finish export Modx"