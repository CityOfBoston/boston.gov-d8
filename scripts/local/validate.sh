#!/bin/bash

# Define colors
Black='\033[0;30m'
Red='\033[1;31m'
LightRed='\033[0;31m'
Green='\033[0;32m'
LightGreen='\033[1;32m'
RedBG='\033[41;1;37m'
GreenBG='\033[42;30m'
YellowBG='\e[103;1;30m'
Bold='\e[1m'
BoldOff='\e[21m'
InverseOn='\e[7m'
InverseOff='\e[27m'
NC='\033[0m'

# This function runs a syntax check on selected set of files and gives a pass/fail report.
function lint() {
    printout "FUNCTION" "$(basename $BASH_SOURCE).lint()" "Called from $(basename $0)"
    printout "ACTION" "Runing PHP linting checks for syntax errors.\n"
    ${REPO_ROOT}/vendor/bin/parallel-lint \
        -e php,module,inc,install \
        --no-progress \
        ${REPO_ROOT}/docroot/modules/custom &&
        ${REPO_ROOT}/vendor/bin/parallel-lint \
            -e php,module,inc,install \
            --no-progress \
            ${REPO_ROOT}/docroot/themes/custom

    if [[ $? -ne 0 ]]; then
        printf "\n"
        printf "${RedBG} ----------------------------------------------------${NC}\n"
        printf "${RedBG} PHP linting errors found - see information above.${NC}\n"
        printf "${RedBG} ----------------------------------------------------${NC}\n\n"
        exit 1
    fi

}

# This function runs a PHPCodesniff across a selected set of files, and produces a report.
function phpcs() {
    printout "FUNCTION" "$(basename $BASH_SOURCE).phpcs()" "Called from $(basename $0)"
    printout "ACTION" "Preparing PHPCS."

    if [[ ! -d ${REPO_ROOT}/docroot/modules/custom ]] || [[ ! -d ${REPO_ROOT}/docroot/themes/custom ]]; then
        printout "ERROR" "Build does not contain custom code folders."
        ls -la ${REPO_ROOT}/docroot/modules
        exit 1
    fi

    ${REPO_ROOT}vendor/bin/phpcs --config-set ignore_warnings_on_exit 1 &> /dev/null
    ${REPO_ROOT}vendor/bin/phpcs --config-set colors 1 &> /dev/null

    printout "ACTION" "Running PHPCS Drupal Standards tests on project files."
    err1=0
    err2=0
    ${REPO_ROOT}/vendor/bin/phpcs \
        --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,*.min.js,**/dist/*.js,**/patterns/*.js,**/bos_web_app/*.js" \
        --report-full="${REPO_ROOT}/setup/err.code_sniffer.standards.txt" \
        --report="summary" \
        --standard=Drupal\
        -n \
        ${REPO_ROOT}/docroot/modules/custom \
        ${REPO_ROOT}/docroot/themes/custom &&
        printout "SUCCESS" "Standards PASSED" || err1=1
    printout "ACTION" "Running PHPCS Drupal Best Practice tests on project files."
    ${REPO_ROOT}/vendor/bin/phpcs \
        --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,*.min.js,**/dist/*.js,**/patterns/*.js,**/bos_web_app/*.js" \
        --report-full="${REPO_ROOT}/setup/err.code_sniffer.bestpractice.txt" \
        --report="summary" \
        --standard=DrupalPractice\
        -n \
        ${REPO_ROOT}/docroot/modules/custom \
        ${REPO_ROOT}/docroot/themes/custom &&
        printout "SUCCESS" "Best Practice PASS" || err2=1

    if [[ $err -eq 0 ]]; then
        printout "SUCCESS" "${GreenBG}Coding standards and Best Practices OK.${NC}\n"
        rm -rf ${REPO_ROOT}/setup/err.code_sniffer.standards.txt
        rm -rf ${REPO_ROOT}/setup/err.code_sniffer.bestpractice.txt
    else
        if [[ -n ${LANDO_APP_NAME} ]]; then
            LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"
        else
            LANDO_APP_URL="https://boston.lndo.site"
        fi
        if [[ $err1 -eq 1 ]]; then
            printout "FAIL" "${RedBG}PHPCS ERRORS FOUND${NC}\n"
            if [[ -e ${REPO_ROOT}/setup/err.code_sniffer.standards.txt ]]; then
              cat ${REPO_ROOT}/setup/err.code_sniffer.standards.txt
              printout "NOTICE" "${Red}See results at ${REPO_ROOT}/setup/err.code_sniffer.standards.txt${NC}"
              printf "         - ${LANDO_APP_URL}/sites/default/files/setup/err.code_sniffer.standards.txt\n\n"
            fi
        fi
        if [[ $err2 -eq 1 ]]; then
            printout "WARNING" "${YellowBG}PHPCS Best Practice issues found${NC}"
            if [[ -e ${REPO_ROOT}/setup/err.code_sniffer.bestpractice.txt ]]; then
              cat ${REPO_ROOT}/setup/err.code_sniffer.bestpractice.txt
              printout "NOTICE" "${Red}See results at ${REPO_ROOT}/setup/err.code_sniffer.bestpractice.txt${NC}"
              printf "         - ${LANDO_APP_URL}/sites/default/files/setup/err.code_sniffer.bestpractice.txt\n\n"
            fi
        fi
        # Raise an error if the standards failed, otherwise no error.
        if [[ $err1 -eq 1 ]]; then
          exit 1
        fi

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
        if [[ -n ${2} ]] && [[ "${2}" != "pull_request" ]]; then
            printout "NOTICE" "Code validation is only performed on Pull Requests."
        fi
        printf "\n"
        printout "NOTICE" "Running code validation checks."

        lint $args && phpcs $args

        if [[ $? -ne 0 ]]; then
            printout "FAIL" "${Red}Checks failed.${NC}\n"
            exit 1
        fi
    fi
fi
