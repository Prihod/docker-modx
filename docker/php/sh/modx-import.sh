#!/bin/bash
set -e

echo "Start Import Modx"

if [ "$MODX_IMPORT" == "latest" ]; then
  archive_file=$(find "$MODX_BACKUP_PATH" -name "*.zip" -type f -printf '%T@ %p\n' | sort -nr | head -n 1 | cut -d' ' -f2-)
else
  if [[ "$MODX_IMPORT" =~ \.zip$ ]]; then
   archive_file="${MODX_BACKUP_PATH}/${MODX_IMPORT}"
  else
    archive_file="${MODX_BACKUP_PATH}/${MODX_IMPORT}.zip"
  fi
fi

if [[ -z "$archive_file" || ! -f "$archive_file" ]]; then
  echo "Import zip file not found in directory: ${MODX_BACKUP_PATH}"
  exit 1
fi

temp_dir=$(mktemp -d)
cd "$temp_dir"

echo "Copy ${archive_file} to ${temp_dir}"
cp "$archive_file" "$temp_dir"

echo "Unzip ${archive_file}"
unzip "$archive_file"

if [[ "$MODX_IMPORT_SITE" -eq 1 ]]; then
   site_zip_file="${temp_dir}/site.zip"
   if [[ -f "$site_zip_file" ]]; then
       echo "Start site import from file: ${site_zip_file}"
       bash "${ENTRYPOINT_PATH}/modx-clear-site.sh";

       echo "Unzip ${site_zip_file}..."
       unzip -q "$site_zip_file" -d "$ROOT_PATH"

       echo "Finish site import"
   else
    echo "Site import zip file: ${site_zip_file} not found!"
   fi
fi

if [[ "$MODX_IMPORT_DB" -eq 1 ]]; then
  db_file="${temp_dir}/db.dump.sql"
  if [[ -f "$db_file" ]]; then
    bash "${ENTRYPOINT_PATH}/modx-clear-db.sh";
    echo "Start DB import from dump file: ${db_file}"
    mysql -u"$MODX_DB_USER" -p"$MODX_DB_PASSWORD" -h"$MODX_DB_SERVER" "$MODX_DB_NAME" < "$db_file"
    echo "Finish DB import"
  else
     echo "DB import dump file: ${db_file} not found!"
  fi
fi

echo "Finish Import Modx"