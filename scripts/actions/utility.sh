#!/bin/bash
#################################################
# Script utility.sh
# Author: David Upton <david.upton@boston.gov>
#
# Runs on a Github actions runner machine.
# Provides some common utility scripts/functions.
#################################################
# Define colors
Default='\e[39m'
Black='\e[30m'
Red='\e[31m'
Green='\e[32m'
Yellow='\e[33m'
Blue='\e[34m'
Magenta='\e[35m'
Orange='\e[48;5;172m'
Cyan='\e[36m'
LightGray='\e[37m'
DarkGray='\e[90m'
LightRed='\e[91m'
LightGreen='\e[92m'
LightYellow='\e[93m'
LightBlue='\e[94m'
LightMagenta='\e[95m'
LightCyan='\e[96m'
White='\e[97m'
RedBG='\e[41;1;97m'
GreenBG='\e[42;1;97m'
YellowBG='\e[103;1;30m'
NC='\e[0m'
Bold='\e[1m'
BoldOff='\e[21m'
InverseOn='\e[7m'
InverseOff='\e[27m'
DimOn='\e[2m'
DimOff='\e[22m'

function printout () {

    if [[ -z ${quiet} ]]; then quiet="0";  fi

    if [[ "${quiet}" != "1" ]]; then

        if [[ "${1}" == "ERROR" ]] || [[ "${1}" == "FAIL" ]]; then
            col1=${InverseOn}${Bold}${LightRed}
            col2=${LightRed}
            col3=${LightRed}${DimOn}
        elif [[ "${1}" == "WARN" ]] || [[ "${1}" == "WARNING" ]] || [[ "${1}" == "ALERT" ]]; then
            col1=${InverseOn}${Bold}${Orange}
            col2=${Orange}
            col3=${Orange}${DimOn}
        elif [[ "${1}" == "SUCCESS" ]]; then
            col1=${InverseOn}${Bold}${Green}
            col2=${Green}
            col3=${DimOn}${Green}
        elif [[ "${1}" == "ACTION" ]]; then
            col1=${InverseOn}${Bold}${LightBlue}
            col2=${LightBlue}
            col3=${DimOn}${LightBlue}
        elif [[ "${1}" == "STEP" ]] || [[ "${1}" == "INFO" ]] || [[ "${1}" == "STATUS" ]]; then
            col1=${InverseOn}${Bold}${Blue}
            col2=${Blue}
            col3=${DimOn}${Blue}
        elif [[ "${1}" == "LANDO" ]]; then
            col1=${InverseOn}${Bold}${Cyan}
            col2=${Cyan}
            col3=${DimOn}${Cyan}
        elif [[ "${1}" == "SCRIPT" ]] || [[ "${1}" == "FUNCTION" ]]; then
            col1=${InverseOn}${Bold}${Cyan}
            col2=${Cyan}
            col3=${DimOn}${Cyan}
        else
            col1=${InverseOn}${Bold}${Default}
            col2=${Default}
            col3=${DimOn}${Default}
        fi

        if [[ -n "${1}" ]]; then
            printf "${col1}[${1}]${NC}"
        fi
        if [[ -n "${2}" ]]; then
              printf " ${col2}${2}${NC}"
        fi
        if [[ -n "${3}" ]]; then
            printf "${col2} - ${3}${NC}"
        fi
        printf "\n"
    fi
}

function importConfigs() {
  # Imports the configurations - Remember the config_split module is enabled, so ensure the correct
  # config_split profile is active.
  # The active config_split profile is usually set by overrides in the settings.php file (or an include in that file).

  CONFIG_DIR=${1}

  # Always be sure the config and config_split modules are enabled.
  ${drush_cmd} pm:enable config, config_split
  ${drupal_cmd} config:import:single --file="${CONFIG_DIR}/config_split.config_split.travis.yml"
  ${drush_cmd} cr &> /dev/null

  # Import the configs - remember... config_split is enabled.
  # Sometimes the import needs to run multiple times to come up clear. IDK
  counter=1
  diff=""
  ${drush_cmd} cr &> /dev/null
  until [[ $diff ]] || [[ $counter -gt 5 ]]; do
    printf "[CONFIG-IMPORT] Iteration #%s Starts\n" "${counter}"
    ${drush_cmd} config:import
    printf "[CONFIG-IMPORT] Iteration #%s Ends\n\n" "${counter}"
    diff=$(${drush_cmd} config:status --state='Different,Only in sync dir' 2>&1 | grep "No differences")
    if [[ ! $diff ]] && [[ $counter -gt 1 ]]; then
        diff=$(grep -Fq '[success]' "${TEMPFILE}"  &> /dev/null && echo 1 || echo 0)
    fi
    ((counter++))
  done

  if [[ $diff ]]; then
    printf "\n[RESULT] Configurations were imported successfully.\n\n"
  else
    slackErrors="${slackErrors}\n- :small_orange_diamond: Problem importing configs."
    printf "\n=== Config Import failed after 5 attempts. Log Output follows ==============\n\n"
    return 1
  fi

  return 0

}

# Synchronize the Site UUID in Database with UUID in system.site.yml
# Argument 1 is the path to the config files, and argument 2 directs whether the DB or config file is updated.
function verifySiteUUID() {
  # Each Drupal site has a unique site UUID.
  # If we have exported configs from an existing site, and try to import them into a new (or different) site, then
  # Drupal recognizes this and prevents the entire import.
  # Since the configs saved in the repo are from a different site than the one we have just created, the UUID in
  # the configs wont match the UUID in the database.  To continue, we need to update the UUID of the new site to
  # be the same as that in the </config/default/system.site.yml> file.

  configPath="${1}"
  if [[ -s ${configPath}/system.site.yml ]]; then
    # Fetch site UUID from the configs in the (newly made) database.
    db_uuid=$(${drush_cmd} @self config:get "system.site" "uuid" | grep -Eo "\s[0-9a-h\-]*")
    # Fetch the site UUID from the configuration file.
    yml_uuid=$(cat ${configPath}/system.site.yml | grep "uuid:" | grep -Eo "\s[0-9a-h\-]*")

    if [[ "${db_uuid}" != "${yml_uuid}" ]]; then
      # The config UUID is different to the UUID in the database.
      printout "NOTICE" "UUID in database needs to be updated to ${yml_uuid}."

      # Change the databases UUID to match the config files UUID.
      ${drush_cmd} @self config:set "system.site" "uuid" ${yml_uuid} -y &>/dev/null

      if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "UUID in database is updated."
      else
        printout "WARNING" "Updating UUID in database failed."
        return 1
      fi
    fi
  fi
}
