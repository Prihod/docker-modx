#!/bin/bash
set -e
prepare_table_prefix() {
    input=$1
    if [[ $input =~ ^random:([0-9]+)$ ]]; then
        length=${BASH_REMATCH[1]}
        random_string=$(head /dev/urandom | tr -dc 'A-Za-z0-9' | head -c "$length")
        echo "${random_string}_"
    else
        echo "$input"
    fi
}

bash "${ENTRYPOINT_PATH}/modx-download.sh"

echo "Install MODX preparing..."
install_config=$(cat "$ROOT_PATH/setup/config.dist.new.xml")

table_prefix=$(prepare_table_prefix "$MODX_TABLE_PREFIX")
install_config="${install_config//<table_prefix>modx_/<table_prefix>$table_prefix}"
echo "  Param table_prefix: $table_prefix"

install_config=$(echo $install_config | sed "s/<database_server>localhost/<database_server>$MODX_DB_SERVER/g")
install_config="${install_config//<database_server>localhost/<database_server>$MODX_DB_SERVER}"
echo "  Param database_server: $MODX_DB_SERVER"

install_config="${install_config//<database>modx_modx/<database>$MODX_DB_NAME}"
echo "  Param database: $MODX_DB_NAME"

install_config="${install_config//<database_user>db_username/<database_user>$MODX_DB_USER}"
echo "  Param database_user: $MODX_DB_USER"

install_config="${install_config//<database_password>db_password/<database_password>$MODX_DB_PASSWORD}"
echo "  Param database_password: $MODX_DB_PASSWORD"

install_config="${install_config//<database_connection_charset>utf8/<database_connection_charset>$MODX_DB_CONNECTION_CHARSET}"
echo "  Param database_connection_charset: $MODX_DB_CONNECTION_CHARSET"

install_config="${install_config//<database_charset>utf8/<database_charset>$MODX_DB_CHARSET}"
echo "  Param database_charset: $MODX_DB_CHARSET"

install_config="${install_config//<database_collation>utf8_general_ci/<database_collation>$MODX_DB_CHARSET_COLLATION}"
echo "  Param database_collation: $MODX_DB_CHARSET_COLLATION"

install_config="${install_config//<https_port>443/<https_port>$MODX_HTTPS_PORT}"
echo "  Param https_port: $MODX_HTTPS_PORT"

install_config="${install_config//<http_host>localhost/<http_host>$MODX_HTTP_HOST}"
echo "  Param http_host: $MODX_HTTP_HOST"

install_config="${install_config//<inplace>0/<inplace>1}"
echo "  Param inplace: 1"

install_config="${install_config//<unpacked>0/<unpacked>1}"
echo "  Param unpacked: 1"

install_config="${install_config//<language>en/<language>$MODX_LANGUAGE}"
echo "  Param: language :$MODX_LANGUAGE"

install_config="${install_config//<cmsadmin>username/<cmsadmin>$MODX_CMS_ADMIN}"
echo "  Param cmsadmin: $MODX_CMS_ADMIN"

install_config="${install_config//<cmspassword>password/<cmspassword>$MODX_CMS_PASS}"
echo "  Param cmspassword: $MODX_CMS_PASS"

install_config="${install_config//<cmsadminemail>email@address.com/<cmsadminemail>$MODX_CMS_EMAIL}"
echo "  Param cmsadminemail: $MODX_CMS_EMAIL"

install_config="${install_config//<remove_setup_directory>1/<remove_setup_directory>$MODX_REMOVE_SETUP_DIRECTORY}"
echo "  Param remove_setup_directory: $MODX_REMOVE_SETUP_DIRECTORY"

install_config="${install_config//<core_path>\/www\/modx\/core\//<core_path>$MODX_CORE_PATH}"
echo "  Param core_path: $MODX_CORE_PATH"

install_config="${install_config//<context_connectors_path>\/www\/modx\/connectors\//<context_connectors_path>$MODX_CONNECTORS_PATH}"
echo "  Param context_connectors_path: $MODX_CONNECTORS_PATH"

install_config="${install_config//<context_connectors_url>\/modx\/connectors\//<context_connectors_url>$MODX_CONNECTORS_URL}"
echo "  Param: context_connectors_url: $MODX_CONNECTORS_URL"

install_config="${install_config//<context_mgr_path>\/www\/modx\/manager\//<context_mgr_path>$MODX_MANAGER_PATH}"
echo "  Param context_mgr_path: $MODX_MANAGER_PATH"

install_config="${install_config//<context_mgr_url>\/modx\/manager\//<context_mgr_url>$MODX_MANAGER_URL}"
echo "  Param context_mgr_url: $MODX_MANAGER_URL"

install_config="${install_config//<context_web_path>\/www\/modx\//<context_web_path>$MODX_BASE_PATH}"
echo "  Param context_web_path: $MODX_BASE_PATH"

install_config="${install_config//<context_web_url>\/modx\//<context_web_url>$MODX_BASE_URL}"
echo "  Param context_web_url: $MODX_BASE_URL"

echo "$install_config" > "$ROOT_PATH"/setup/config.xml

echo "Start installation Modx..."
php "$ROOT_PATH"/setup/index.php --installmode=new

echo "Modx installation is complete!"