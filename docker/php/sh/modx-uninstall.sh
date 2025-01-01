#!/bin/bash
set -e

echo "Start uninstall Modx"
bash "${ENTRYPOINT_PATH}/modx-clear-site.sh";
bash "${ENTRYPOINT_PATH}/modx-clear-db.sh";
echo "Finish uninstall Modx"