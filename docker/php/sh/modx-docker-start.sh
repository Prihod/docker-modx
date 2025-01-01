#!/bin/bash

if [ "$SSH_ENABLE" -eq 1 ]; then
    /usr/sbin/sshd
fi

bash "${ENTRYPOINT_PATH}/docker-php-entrypoint"

if [[ "$SSL_GENERATE" -eq 1 &&  ! -f "${SSL_PATH}/server.crt" ]]; then
    bash "${ENTRYPOINT_PATH}/modx-generate-ssl.sh";
fi

if [[ "$MODX_INSTALL_ENABLE" -eq 1 ]]; then

  version_file="${MODX_CORE_PATH}docs/version.inc.php"
  if [[ "$MODX_RESET" -eq 1 && -f "$version_file" ]]; then
      bash "${ENTRYPOINT_PATH}/modx-uninstall.sh";
  fi

  if [[ "$MODX_IMPORT" != "0" ]]; then
    bash "${ENTRYPOINT_PATH}/modx-import.sh";
  elif [ ! -f "$version_file" ]; then
        bash "${ENTRYPOINT_PATH}/modx-install.sh";
        if [ "$MODX_CONFIGURE_ENABLE" -eq 1 ]; then
          bash "${ENTRYPOINT_PATH}/modx-configure.sh";
        fi
  else
        echo "Starting check upgrade Modx version"
        installed_version=$(php -r "\$v = include '$version_file'; echo \$v['full_version'];")

        echo "Installed Modx version: $installed_version"
        echo "Required Modx version: $MODX_VERSION"

        is_less=$(php -r "echo  version_compare('$installed_version','$MODX_VERSION', '<');")

        if [[ -n "$is_less" && "$is_less" -eq 1 ]]; then
            bash "${ENTRYPOINT_PATH}/modx-upgrade.sh";
          else
           echo "No need to upgrade the Modx version"
        fi
  fi
fi

if ! pgrep php-fpm; then
    php-fpm
fi