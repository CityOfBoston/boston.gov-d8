#!/bin/bash
# This script is used to create a default/template web_app application

    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    target_env="local"

    # Define the root folder for this template
    TEMPLATE_DIR=${REPO_ROOT}/${webapps_local_local_dir}/$1

    # Create the root folder if its not there already
    if (( ! -d ${TEMPLATE_DIR} )); then
        md -p ${TEMPLATE_DIR}
    else
        echo "Template folder already exists !"
        exit 0
    fi

    # Create the folder strcture
    cd ${TEMPLATE_DIR} && \
        mkdir -p src/css && \
        mkdir -p src/js && \
        mkdir -p dist

    # Now create the template files.
    touch ${TEMPLATE_DIR}/${1}.info.yml
    touch ${TEMPLATE_DIR}/${1}.libraries.yml
    touch ${TEMPLATE_DIR}/${1}.module
    touch ${TEMPLATE_DIR}/package.json
    touch ${TEMPLATE_DIR}/cob.json
    touch ${TEMPLATE_DIR}/manifest.json
    touch ${TEMPLATE_DIR}/gulpfile.js
    touch ${TEMPLATE_DIR}/babel.config.json

    # Now link in the fixed resources
    ln -s  ${LANDO_MOUNT}/scripts/local/template/index.html ${TEMPLATE_DIR}/index.html
    ln -s  ${LANDO_MOUNT}/scripts/local/template/README.md ${TEMPLATE_DIR}/README.md
    ln -s  ${LANDO_MOUNT}/scripts/local/template/.gitignore ${TEMPLATE_DIR}/.gitignore