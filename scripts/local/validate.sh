#!/bin/bash

# Define colors
Black='\033[0;30m'
Red='\033[1;31m'
LightRed='\033[0;31m'
Green='\033[0;32m'
LightGreen='\033[1;32m'
RedBG='\033[41;1;37m'
GreenBG='\033[42;30m'
NC='\033[0m'

# This function runs a syntax check on selected set of files and gives a pass/fail report.
function lint() {
    printf "[notice] Runing PHP linting checks for syntax errors.\n"
    vendor/bin/parallel-lint \
        -e php,module,inc,install \
        --exclude **/core \
        --exclude docroot/modules/contrib/ \
        --no-progress \
        docroot

    if [[ $? -ne 0 ]]; then
        printf "\n ${RedBG}PHP linting errors found - see information above.${NC}\n"
        exit 1
    fi

}

# This function runs a PHPCodesniff across a selected set of files, and produces a report.
function phpcs() {
    printf "[notice] Setting PHPCS options.\n"
    vendor/bin/phpcs --config-set ignore_warnings_on_exit 1 &> /dev/null
    vendor/bin/phpcs --config-set colors 1 &> /dev/null

    printf "[notice] Running PHPCS on project files.\n"
    vendor/bin/phpcs --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,**/dist/*.js,**/patterns/*.js" \
        --report-full="${LANDO_MOUNT}/setup/err.code_sniffer.txt" \
        --report="summary" \
        --standard="${LANDO_MOUNT}/vendor/drupal/coder/coder_sniffer/Drupal/ruleset.xml" \
        -n \
        /app/docroot/modules/custom \
        /app/docroot/themes/custom

    if [[ $? -eq 0 ]]; then
        printf "${GreenBG}Coding standards OK.${NC}\n\n"
        rm -rf ${LANDO_MOUNT}/setup/err.code_sniffer.txt
    else
        printf "${RedGB}PHPCS ERRORS FOUND${NC}\n"
        cat /app/setup/err.code_sniffer.txt
        LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"
        printf "${Red}[notice] See results at ${LANDO_APP_URL}/sites/default/files/err.code_sniffer.txt${NC}\n\n"
        exit 1
    fi
}

# Get the first word from user input.
command=$1
# Get everything after the first word from input.
args=${@:2}

# Read in config and variables.
. "${LANDO_MOUNT}/scripts/local/lando_utilities.sh"
# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then
    if [[ $command == "lint" ]]; then
        lint $args
    elif [[ $command == "phpcs" ]]; then
        phpcs $args
    elif [[ $command == "all" ]]; then
        lint $args && phpcs $args
    fi
fi