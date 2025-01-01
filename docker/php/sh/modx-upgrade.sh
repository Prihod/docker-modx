#!/bin/bash
set -e

bash "${ENTRYPOINT_PATH}/modx-download.sh"

echo "Upgrade MODX preparing..."
upgrade_config=$(cat "$ROOT_PATH/setup/config.dist.upgrade.xml")

upgrade_config="${upgrade_config//<inplace>0/<inplace>1}"
echo "  Param inplace: 1"

upgrade_config="${upgrade_config//<unpacked>0/<unpacked>0}"
echo "  Param unpacked: 0"

upgrade_config="${upgrade_config//<language>en/<language>$MODX_LANGUAGE}"
echo "  Param: language :$MODX_LANGUAGE"

echo "$upgrade_config" > "$ROOT_PATH"/setup/config.xml

echo "Start of modx upgrade..."
php "$ROOT_PATH"/setup/index.php --installmode=upgrade

echo "Modx upgrade is complete!"
