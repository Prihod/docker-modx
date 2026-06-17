#!/bin/bash
set -e

source_file="${MODX_CACHE_SOURCE_PATH}/modx-${MODX_VERSION}.zip"
modx_download_url="https://modx.com/download/direct/modx-${MODX_VERSION}.zip"

if [[ ! -f "$source_file" ]]; then
    echo "Downloading Modx ${MODX_VERSION} ..."
    echo "Download Modx URL ${modx_download_url}"
    curl -o "${MODX_CACHE_SOURCE_PATH}/modx-${MODX_VERSION}.zip" -L "${modx_download_url}"
fi


echo "Unzip Modx..."
# -o keeps startup non-interactive when modx-${MODX_VERSION} already exists.
unzip -oq "$source_file" -d "${ROOT_PATH}"

echo "Moving extracted Modx files..."
cp -rf "${ROOT_PATH}"/modx-"${MODX_VERSION}"/* "${ROOT_PATH}"
#mv "${ROOT_PATH}"/modx-"${MODX_VERSION}"/* "${ROOT_PATH}"

echo "Deleting temporary Modx files..."
rm -rf "${ROOT_PATH}"/modx-"${MODX_VERSION}"

if [[ "$MODX_USE_CACHE_SOURCE" -ne 1 && -f "$source_file" ]]; then
  rm "$source_file"
fi

echo "Set permissions Modx files..."
chown -R www-data:www-data "${ROOT_PATH}"
chmod -R 775 "${ROOT_PATH}"