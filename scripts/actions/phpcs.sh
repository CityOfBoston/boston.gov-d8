#!/bin/bash
#################################################
# Script phpcs.sh
# Author: David Upton <david.upton@boston.gov>
#
# Runs on a Github actions runner machine.
# Performs basic phpcs checks on the php files
#  specified.
#################################################
function phpcs() {

    pwd=$(pwd);
    # Include our utility functions.
    source "${pwd}/utility.sh"

    root=${1}

    printout "FUNCTION" "$(basename $BASH_SOURCE).phpcs()" "Called from $(basename $0)"
    printout "ACTION" "Preparing PHPCS."

    if [[ ! -d ${root}/docroot/modules/custom ]] || [[ ! -d ${root}/docroot/themes/custom ]]; then
        printout "ERROR" "Build does not contain custom code folders."
        ls -la ${root}/docroot/modules
        exit 1
    fi

    ${root}/vendor/bin/phpcs --config-set ignore_warnings_on_exit 1 &> /dev/null
    ${root}/vendor/bin/phpcs --config-set colors 1 &> /dev/null

    printout "ACTION" "Running PHPCS Drupal Standards tests on project files."
    err1=0
    err2=0
    ${root}/vendor/bin/phpcs \
        --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,*.min.js,**/dist/*.js,**/patterns/*.js,**/bos_web_app/*.js" \
        --report-full="${root}/setup/err.code_sniffer.standards.txt" \
        --report="summary" \
        --standard=Drupal\
        -n \
        ${root}/docroot/modules/custom \
        ${root}/docroot/themes/custom &&
        printout "SUCCESS" "Standards PASSED" || err1=1
    printout "ACTION" "Running PHPCS Drupal Best Practice tests on project files."
    ${root}/vendor/bin/phpcs \
        --extensions="php/php,module/php,inc/php,install/php,theme/php,js/js" \
        --ignore="*.tpl.php,*.css,*.yml,*.twig,*.md,*.min.js,**/dist/*.js,**/patterns/*.js,**/bos_web_app/*.js" \
        --report-full="${root}/setup/err.code_sniffer.bestpractice.txt" \
        --report="summary" \
        --standard=DrupalPractice\
        -n \
        ${root}/docroot/modules/custom \
        ${root}/docroot/themes/custom &&
        printout "SUCCESS" "Best Practice PASS" || err2=1

    if [[ $err -eq 0 ]]; then
        printout "SUCCESS" "${GreenBG}Coding standards and Best Practices OK.${NC}\n"
        rm -rf ${root}/setup/err.code_sniffer.standards.txt
        rm -rf ${root}/setup/err.code_sniffer.bestpractice.txt
    else
        if [[ -n ${LANDO_APP_NAME} ]]; then
            LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"
        else
            LANDO_APP_URL="https://boston.lndo.site"
        fi
        if [[ $err1 -eq 1 ]]; then
            printout "FAIL" "${RedBG}PHPCS ERRORS FOUND${NC}\n"
            if [[ -e ${root}/setup/err.code_sniffer.standards.txt ]]; then
              cat ${root}/setup/err.code_sniffer.standards.txt
              printout "NOTICE" "${Red}See results at ${root}/setup/err.code_sniffer.standards.txt${NC}"
              printf "         - ${LANDO_APP_URL}/sites/default/files/setup/err.code_sniffer.standards.txt\n\n"
            fi
        fi
        if [[ $err2 -eq 1 ]]; then
            printout "WARNING" "${YellowBG}PHPCS Best Practice issues found${NC}"
            if [[ -e ${root}/setup/err.code_sniffer.bestpractice.txt ]]; then
              cat ${root}/setup/err.code_sniffer.bestpractice.txt
              printout "NOTICE" "${Red}See results at ${root}/setup/err.code_sniffer.bestpractice.txt${NC}"
              printf "         - ${LANDO_APP_URL}/sites/default/files/setup/err.code_sniffer.bestpractice.txt\n\n"
            fi
        fi
        # Raise an error if the standards failed, otherwise no error.
        if [[ $err1 -eq 1 ]]; then
          exit 1
        fi

    fi
}
