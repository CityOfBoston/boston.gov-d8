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
    ${REPO_ROOT}/vendor/bin/parallel-lint \
        -e php,module,inc,install \
        --exclude **/core \
        --exclude docroot/modules/contrib/ \
        --no-progress \
        ${REPO_ROOT}/docroot

    if [[ $? -ne 0 ]]; then
        printf "\n ${RedBG}PHP linting errors found - see information above.${NC}\n"
        exit 1
    fi

}

# This function runs a PHPCodesniff across a selected set of files, and produces a report.
function phpcs() {
    printf "[notice] Setting PHPCS options.\n"

    if [[ ! -d ${REPO_ROOT}/docroot/modules/custom ]] || [[ ! -d ${REPO_ROOT}/docroot/themes/custom ]]; then
        printout "ERROR" "Build does not contain custom code folders."
        ls -la ${REPO_ROOT}/docroot/modules
        exit 1
    fi

    ${REPO_ROOT}vendor/bin/phpcs --config-set ignore_warnings_on_exit 1 &> /dev/null
    ${REPO_ROOT}vendor/bin/phpcs --config-set colors 1 &> /dev/null

    printout "[notice]" "Running PHPCS on project files."
    ${REPO_ROOT}vendor/bin/phpcs \
        --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,**/dist/*.js,**/patterns/*.js" \
        --report-full="${REPO_ROOT}/setup/err.code_sniffer.txt" \
        --report="summary" \
        --standard="${REPO_ROOT}/vendor/drupal/coder/coder_sniffer/Drupal/ruleset.xml" \
        -n \
        ${REPO_ROOT}/docroot/modules/custom \
        ${REPO_ROOT}/docroot/themes/custom

    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "${GreenBG}Coding standards OK.${NC}\n"
        rm -rf ${REPO_ROOT}/setup/err.code_sniffer.txt
    else
        printout "FAIL" "${RedGB}PHPCS ERRORS FOUND${NC}\n"
        cat /app/setup/err.code_sniffer.txt
        LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"
        printout "NOTICE" "${Red}See results at ${LANDO_APP_URL}/sites/default/files/err.code_sniffer.txt${NC}\n"
        exit 1
    fi
}

# Get the first word from user input.
command=$1
# Get everything after the first word from input.
args=${@:2}

if [[ -z $REPO_ROOT ]]; then
    if [[ -n ${LANDO_MOUNT} ]]; then REPO_ROOT="${LANDO_MOUNT}"
    elif [[ -n ${TRAVIS_BUILD_DIR} ]]; then REPO_ROOT="${TRAVIS_BUILD_DIR}"
    else REPO_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && cd ../../ && pwd )"
    fi
fi

# Read in config and variables.
. "${REPO_ROOT}/scripts/cob_build_utilities.sh"
# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then

    if [[ $command == "lint" ]]; then
        lint $args
    elif [[ $command == "phpcs" ]]; then
        phpcs $args
    elif [[ $command == "all" ]]; then
        printf "\n"
        printout "[info]" "Running code validation checks.\n"
        lint $args && phpcs $args
        if [[ $? -eq 0 ]]; then
            exit 0
        else
            printf "\n${Red}[fail] Checks failed.{NC}\n\n"
            exit 1
        fi
    fi
fi