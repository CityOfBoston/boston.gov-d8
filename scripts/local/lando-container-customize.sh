#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker container.
  ###############################################################

    printf "\033[1;32m[lando]\033[1;33m Building; Installing Linux packages in appserver container...\033[0m\n"
    if [ ! -e  /app/setup ]; then
        mkdir /app/setup &&
            chown www-data:www-data /app/setup &&
            chmod 777 /app/setup;
    fi
    printf "\033[1;32m[lando]\033[1;33m -> Container build actions will be logged to files in ${LANDO_MOUNT}/setup\033[0m\n"

    # Updates apt, creates and pipes output to setup/lando.log
    mv -f /etc/apt/sources.list /etc/apt/sources.list.bak
    sed -n '/jessie-updates/!p' /etc/apt/sources.list.bak > /etc/apt/sources.list
    apt-get update && apt-get install -y --no-install-recommends apt-utils  > ${LANDO_MOUNT}/setup/lando.log

    # Installs linux apps and extensions into the appserver container.
    apt-get install -y --no-install-recommends zip unzip bzip2 libbz2-dev libgd-dev mysql-client openssh-client vim jq cron renameutils rename >> ${LANDO_MOUNT}/setup/lando.log
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ >> ${LANDO_MOUNT}/setup/lando.log

    # Change the permissions on the log file so that non-root user can add to log.
    chmod 777 ${LANDO_MOUNT}/setup/lando.log >> ${LANDO_MOUNT}/setup/lando.log

    # [Optional]  Do not need the next 2 statements on stage or production servers.
    # Install a custom apache config file for on-demand containers -> limits the apache children to preserve memory.
    #     (Config loaded and enabled by Phing in setup:docker:drupal-terraform)
    cp ${LANDO_MOUNT}/scripts/phing/files/limit-apache-children.conf /etc/apache2/conf-available/ >> ${LANDO_MOUNT}/setup/lando.log
    cp ${LANDO_MOUNT}/scripts/phing/files/boston-dev-php.ini /usr/local/etc/php/conf.d/ >> ${LANDO_MOUNT}/setup/lando.log

    #- ip route | awk 'NR==1 {printf $3}' | xargs echo "xdebug.remote_host=" >>/usr/local/etc/php/conf.d/boston-dev-php.ini
    service apache2 reload >> ${LANDO_MOUNT}/setup/lando.log
    printf "\033[1;32m[lando]\033[1;33m -> Appserver container is built.\033[0m\n\n"
