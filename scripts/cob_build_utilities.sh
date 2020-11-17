#!/bin/bash

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
InverseOn='\e[7m'
InverseOff='\e[27m'
DimOn='\e[2m'
DimOff='\e[22m'

# basic parse of a yml file into a series of variables.
function parse_yaml() {
   local prefix=${2}
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\)\($w\)$s:$s\"\(.*\)\"$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  ${1} |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'${prefix}'",vn, $2, $3);
      }
   }'
}

# Wrapper to load the .lando.yml file
function load_lando_yml() {
    eval $(parse_yaml "${REPO_ROOT}/.lando.yml" "lando_")
}

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
            col3=$${DimOn}{LightBlue}
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
            col3={DimOn}${Default}
        fi

        if [[ -n ${1} ]]; then
            printf "$col1[${1}]${NC}"
        fi
        if [[ -n ${2} ]]; then
              printf " $col2${2}$NC"
        fi
        if [[ -n ${3} ]]; then
            printf "$col2 - ${3}$NC"
        fi
        printf "\n"
    fi
}

function clone_private_repo() {

  printout "FUNCTION" "$(basename $BASH_SOURCE).clone_private_repo()" "Called from $(basename $0)"
  printout "ACTION" "Clone private repo and merge with main repo."

  # Assign a temporary folder.
  if [[ -z "${git_private_repo_local_dir}" ]]; then git_private_repo_local_dir="${REPO_ROOT}/tmprepo"; fi

  # Empty the folder if it exists.
  if [[ -e "${git_private_repo_local_dir}" ]]; then rm -rf ${git_private_repo_local_dir}; fi

  # Clone the repo and merge
  printout "INFO" "Private repo: ${git_private_repo_repo} - Branch: ${git_private_repo_branch} - will be cloned into ${git_private_repo_local_dir}."
  if [[ -n ${GITHUB_TOKEN} ]]; then
    # Will enforce a token which should be passed via and ENVAR.
    REPO_LOCATION="https://${GITHUB_USER}:${GITHUB_TOKEN}@github.com/"
  else
    # Will rely on the user have an SSL cert which is registered with the private repo.
    REPO_LOCATION="git@github.com:"
  fi

  git clone -b ${git_private_repo_branch} ${REPO_LOCATION}${git_private_repo_repo} ${git_private_repo_local_dir} -q --depth 1

  if [[ $? -eq 0 ]]; then
    printout "SUCCESS" "Private repo cloned."
    rm -rf ${git_private_repo_local_dir}/.git &&
        if [[ $? -eq 0 ]]; then printout "INFO" "Detached repository."; fi &&
        find ${git_private_repo_local_dir}/. -iname '*..gitignore' -exec rename 's/\.\.gitignore/\.gitignore/' '{}' \; &&
        if [[ $? -eq 0 ]]; then printout "INFO" "Renamed and applied gitignores."; fi &&
        rsync -aE "${git_private_repo_local_dir}/" "${REPO_ROOT}/" --exclude=*.md &&
        if [[ $? -eq 0 ]]; then printout "INFO" "Merged private repo with main repo."; fi &&
        rm -rf ${git_private_repo_local_dir} &&
        if [[ $? -eq 0 ]]; then printout "INFO" "Tidied up remnants of private repo."; fi

    if [[ $? -ne 0 ]]; then
        printout "ERROR" "Failed to clone/merge private repo."
        exit 1
    fi
  else
    printout "ERROR" "Failed to clone/merge private repo."
    exit 1
  fi

  printout "SUCCESS" "Private repo merge complete."
}

function clone_patterns_repo() {

    printout "FUNCTION" "$(basename $BASH_SOURCE).clone_patterns_repo()" "Called from $(basename $0)"
    printout "ACTION" "Cloning '${patterns_local_repo_branch}' branch of Patterns library into ${patterns_local_repo_local_dir}."

    if [[ -n ${GITHUB_TOKEN} ]]; then
        # Will enforce a token which should be passed via and ENVAR.
        REPO_LOCATION="https://${GITHUB_USER}:${GITHUB_TOKEN}@github.com/"
    else
        # Will rely on the user have an SSL cert which is registered with the private repo.
        REPO_LOCATION="git@github.com:"
    fi

    # If the target folder for the patterns repo does not exist, then create it now.
    if [[ ! -d ${patterns_local_repo_local_dir} ]]; then
        mkdir ${patterns_local_repo_local_dir}
    else
      if [[ "$(ls -A ${patterns_local_repo_local_dir})" ]]; then
        # We cannot clone into a non-empty folder.  This is fatal.
        printout "ERROR" "Patterns folder ${patterns_local_repo_local_dir} is not empty and the build cannot delete it."
        printf "        Please manually delete the patterns folder from the repo root on host machine, and retry the build.\n\n"
        printf "${InverseOn}${Red}SEE ERRORS ABOVE.\n" &>> ${REPO_ROOT}/setup/uli.log;
        printf "Please manually delete the patterns folder from the repo root on the host machine, and retry the build.\n\n${NC}" &>> ${REPO_ROOT}/setup/uli.log;
        exit 1
      fi
    fi
    # Manke sure the folder is properly owned in this container.
    chown node:node ${patterns_local_repo_local_dir}

    # Clone the Patterns repo into the target folder.
    (git clone -b ${patterns_local_repo_branch} ${REPO_LOCATION}${patterns_local_repo_name} ${patterns_local_repo_local_dir} -q --depth 100 &&
      printout "SUCCESS" "Patterns library cloned.\n") || (printout "ERROR" "Patterns library NOT cloned or installed.\n" && exit 1)

    # Make the public folder that gulp and fractal will build into.
    if [[ ! -d ${patterns_local_repo_local_dir}/public ]]; then
        printout "INFO" "Create patterns (Gulp & Fractal) build folders"
        (mkdir ${patterns_local_repo_local_dir}/public &&
          chown node:node ${patterns_local_repo_local_dir}/public &&
          chmod 755 ${patterns_local_repo_local_dir}/public &&
          printout "SUCCESS" "Build folder created at ${patterns_local_repo_local_dir}/public.\n") || printout "WARNING" "Build folder was not created.\n"
    fi

}

function build_settings() {

    printout "FUNCTION" "$(basename $BASH_SOURCE).build_settings()" "Called from $(basename $0)"

    printout "ACTION" "Installing and updating Drupal settings files."

    if [[ -z "${project_docroot}}" ]]; then
        # Read in config and variables.
        eval $(parse_yaml "${REPO_ROOT}/scripts/.config.yml" "")
        eval $(parse_yaml "${REPO_ROOT}/.lando.yml" "lando_")
    fi

    # Set local variables
    settings_path="${project_docroot}/sites/${drupal_multisite_name}"
    settings_file="${settings_path}/settings.php"
    default_settings_file="${settings_path}/default.settings.php"
    services_file="${settings_path}/services.yml"
    default_services_file="${settings_path}/default.services.yml"
    local_settings_file="${settings_path}/settings/settings.local.php"
    default_local_settings_file="${settings_path}/settings/default.local.settings.php"
    private_settings_file="${settings_path}/settings/${git_private_repo_settings_file}"

    # Setup hooks from inside settings.php
    if [[ ! -e ${settings_file} ]]; then
        # Copy default file.
        cp default_settings_file settings_file
        cp default_settings_file settings_file
    fi

    # Setup the local.settings.php file
    if [[ ! -e ${local_settings_file} ]]; then
        # Copy default file.
        cp default_local_settings_file local_settings_file
    fi
    echo -e "\n/*\n * Content added by Lando build.\n */\n" >> ${local_settings_file}
    if [[ -n "${private_settings_file}" ]]; then
        # If a private settings file is defined, then make a reference to it from the local.settings.php file.
        echo -e "\n// Adds a directive to include contents of settings file in repo.\n" >> ${local_settings_file}
        echo -e "if (file_exists(DRUPAL_ROOT . \"/docroot/${git_private_repo_settings_file}\")) {\n" >> ${local_settings_file}
        echo -e "  include DRUPAL_ROOT . \"/docroot/${git_private_repo_settings_file}\";\n" >> ${local_settings_file}
        echo -e "}\n\n" >> ${local_settings_file}
    fi
    # Add in config sync directory from yml.
    echo -e "ini_set('memory_limit', '${project_php_memory_size}');\n" >> ${local_settings_file}
    echo -e "if ((isset(\$_SERVER['REQUEST_URI']) && strpos(\$_SERVER['REQUEST_URI'], 'entity_clone') !== FALSE) || (isset(\$_SERVER['REDIRECT_URL']) && strpos(\$_SERVER['REDIRECT_URL'], 'entity_clone') !== FALSE)) {\n  ini_set('memory_limit', '-1');\n}\n"  >> ${local_settings_file}
    echo -e "\$config_directories[\"sync\"] = \"${build_local_config_sync}\";\n" >> ${local_settings_file}
    echo -e "\$settings[\"install_profile\"] = \"${project_profile_name}\";\n" >> ${local_settings_file}
    echo -e "/* End of Lando build additions. */\n" >> ${local_settings_file}

    # setup the private settings file
#    if [[ -n "${private_settings_file}" ]] && [[ -e ${private_settings_file} ]]; then
#        # There is a private settings file.
#    fi

    # Setup the serices.yml file
    if [[ ! -e ${services_file} ]]; then
        # Copy default file.
        cp default_services_file services_file
    fi

    # Remove un-needed settings files.
    rm -f "${default_settings_file}"
    rm -f "${default_local_settings_file}"
    rm -f "${default_services_file}"
    rm -f "${docroot}/sites/example.settings.local.php"
    rm -f "${docroot}/sites/example.sites.php"

    printout "SUCCESS" "Settings files written/updated.\n"
}

function displayTime() {
  elapsed=${1};
  if (( $elapsed > 3600 )); then
      let "hours=elapsed/3600"
      text="hour"
      if (( $hours > 1 )); then text="hours"; fi
      hours="$hours $text, "
  fi
  if (( $elapsed > 60 )); then
      let "minutes=(elapsed%3600)/60"
      text="minute"
      if (( $minutes > 1 )); then text="minutes"; fi
      minutes="$minutes $text and "
  fi
  let "seconds=(elapsed%3600)%60"
  text="second"
  if (( $seconds > 1 )); then text="seconds"; fi
  seconds="$seconds $text."

  echo "${hours} ${minutes} ${seconds}"
}

function operating_system() {
    case "$OSTYPE" in
      solaris*) echo "SOLARIS" ;;
      darwin*)  echo "OSX" ;;
      linux*)   echo "LINUX" ;;
      bsd*)     echo "BSD" ;;
      msys*)    echo "WINDOWS" ;;
      *)        echo "unknown" ;;
    esac
}

if [[ -z $REPO_ROOT ]]; then
    if [[ -n ${LANDO_MOUNT} ]]; then REPO_ROOT="${LANDO_MOUNT}"
    elif [[ -n ${TRAVIS_BUILD_DIR} ]]; then REPO_ROOT="${TRAVIS_BUILD_DIR}"
    else REPO_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && cd ../../ && pwd )"
    fi
fi

# Read in config and variables.
eval $(parse_yaml "${REPO_ROOT}/.lando.yml" "lando_")
eval $(parse_yaml "${REPO_ROOT}/scripts/.config.yml" "")
setup_logs="${REPO_ROOT}/setup"