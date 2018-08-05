#!/bin/bash

# Define colors
Black='\033[0;30m'
DarkGray='\033[1;30m'
Red='\033[0;31m'
LightRed='\033[1;31m'
Green='\033[0;32m'
LightGreen='\033[1;32m'
BrownOrange='\033[0;33m'
Yellow='\033[1;33m'
Blue='\033[0;34m'
LightBlue='\033[1;34m'
Purple='\033[0;35m'
LightPurple='\033[1;35m'
Cyan='\033[0;36m'
LightCyan='\033[1;36m'
LightGray='\033[0;37m'
White='\033[1;37m'
NC='\033[0m' # No Color

# Define paths (relative to this script)

REPO_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && cd ../../ && pwd )"
SCRIPTS_PATH="$REPO_ROOT/scripts"
DOIT_SCRIPTS_PATH="${SCRIPTS_PATH}/doit"

# Draws "DoIT" in CLI block letters.
function doitbrand() {
    ${DOIT_SCRIPTS_PATH}/branding.sh
  # Image credit: http://www.chris.com/ascii/index.php?art=objects/buildings
}

# Provide some details on how this tool works.
function doithelp() {
  echo $'\r'
  echo -e "${Yellow}Community Commands:${NC}"
  echo -e "${LightBlue}  install <branch>${NC}        Creates and starts containers for first time using <branch>."
  echo -e "${LightBlue}  rebuild full <branch>${NC}   Destroys, rebuilds and starts containers using <branch>."
  echo -e "${LightBlue}  rebuild quick${NC}           Rebuilds Drupal site but does not change customisations."
  echo $'\r'
  echo -e "${Yellow}Staff Commands:${NC}"
  echo $'\r'
}

# Formatting for a simple comment.
function doitcomment() {
  echo $'\r'
  echo -ne "${Yellow}$1${LightGreen} $2${NC}"
  echo $'\r'
  echo $'\r'
}

# basic parse of a yml file into a series of variables.
function parse_yaml() {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\)\($w\)$s:$s\"\(.*\)\"$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}

# Wrapper to load the .lando.yml file
function load_lando_yml() {
    eval $(parse_yaml ".lando.yml" "lando_")
}

# find the current git branch
function get_branch() {
    cd ${REPO_ROOT}
    git branch | grep "\*" | awk '{print $2}'

}

#################################
# START CUSTOM SCRIPTS
#################################

function doitinstall() {
    doitcomment "Install container from scratch."

    # Check if the containers exist, and if they do, destroy them
    if [ "$(docker ps -a -q -f name=${lando_name}_appserver_*)" ]; then
        doitcomment "> Removing (destroying) the existing containers." ""
        cd $REPO_ROOT
        lando destroy -y
        if [ $? -ne 0 ]; then
            doitcomment "FAILED:" "The container could not be deleted."
            exit 1
        fi
    fi
    # remove the apache (appserver) image
    if [ "$(docker images -a | grep -e 'devwithlando.*apache')" ]; then
        docker images -a | grep -e "devwithlando.*apache" | awk '{print $3}' | xargs docker rmi
        if [ $? -ne 0 ]; then
            doitcomment "FAILED:" "The appserver image could not be deleted."
            exit 1
        fi
    fi
    # remove the proxy if it exists
    if [ "$(docker images -a | grep -e 'traefik.*alpine')" ]; then
        # Stop and delete the proxy container
        if [ "$(docker ps -a | grep -e 'traefik.*alpine')" ]; then
            docker ps -a | grep -e "traefik.*alpine" | awk '{print $1}' | xargs docker stop
            docker ps -a | grep "traefik.*alpine" | awk '{print $1}' | xargs docker rm
            if [ $? -ne 0 ]; then
                doitcomment "FAILED:" "The proxy service image could not be removed."
                exit 1
            fi
        fi
        docker images -a | grep "traefik.*alpine" | awk '{print $3}' | xargs docker rmi
        if [ $? -ne 0 ]; then
            doitcomment "FAILED:" "The proxy image could not be deleted."
            exit 1
        fi
    fi
    # Completely remove the existing repo contents.
    REPO="develop"
    if [ ${1} ]; then REPO=${1}; fi
    cd $REPO_ROOT && cd ../

    # check if sudo command is required/available ...
    sudo -v > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        doitcomment "> Removing the existing drupal files and folders." "(i.e. the repo)."
        doitcomment "" "NOTE: repo (branch ${REPO}) will be removed and re-cloned to $REPO_ROOT"
	    rm -rf $REPO_ROOT
    else
        doitcomment "> Removing the existing drupal files and folders." "(i.e. the repo) - may require elevated permissions."
        doitcomment "" "NOTE: repo (branch ${REPO}) will be removed and re-cloned to $REPO_ROOT"
	    sudo rm -rf $REPO_ROOT
    fi

    # Clone the repo locally.
    doitcomment "> Make a new clone of the repo branch ${REPO}." ""
    git clone -b $REPO git@github.com:CityOfBoston/boston.gov-d8.git boston.gov-d8

    # Rebuild the containers and run the install scripts
    doitcomment "> Rebuild the containers and run install scripts." ""
    cd $REPO_ROOT
    cp ./scripts/phing/files/lando.config.yml ~/.lando/config.yml
    lando start
    retVal=$?
    if [ $retVal -ne 0 ]; then
        doitcomment "FAILED:" "Container is NOT built check output above for reasons for failure."
    else
        doitcomment "SUCCESS:" "Container is now built and started."
    fi
    cd $REPO_ROOT
    exit $retVal
}

# Wrapper to re-install the drupal site into existing containers.
function doitrefresh() {
    BRANCH=$(get_branch)
    doitcomment "Re-install Drupal (using current branch ${BRANCH}) into existing container."

    # remove the key existing drupal files and folders
    doitcomment "> Removing key Drupal files and folders." " - On an installed Drupal site this requires elevated permissions."
    doitcomment "" "NOTE: 1. This process does not pull or otherwise refresh the repo
                 \n       2. This process does keep any custom modules, the initial config files and any installed user files.
                 \n       3. This process will operate on repo (branch ${BRANCH}) located at: $REPO_ROOT"
    cd $REPO_ROOT && cd ../
    sudo rm -rf $REPO_ROOT/docroot/core
    sudo rm -f $REPO_ROOT/docroot/sites/default/settings*.php
    sudo rm -rf $REPO_ROOT/docroot/modules/contrib/*
    sudo rm -rf $REPO_ROOT/config/sync/*
    sudo rm -f $REPO_ROOT/setup/*
    sudo rm -f $REPO_ROOT/composer.lock
    sudo chmod -R 777 $REPO_ROOT/docroot/sites/default/

    # Rebuild the Drupal site.
    cd $REPO_ROOT
    lando phing setup:docker:drupal-local
    cd $REPO_ROOT
}

function doitrebuild() {
    if [ $1 == "quick" ]; then
        doitrefresh
    elif [ $1 == "full" ]; then
        doitinstall $2
    else
        echo "Rebuild not specified, execute [q]uick or [f]ull ?"
        read -p "" qf
            case $qf in
                [Qq]* ) doitrefresh; break;;
                [Aa]* ) doitinstall;;
                * ) echo "Please answer q (quick) or f (full).";;
            esac
    fi


}


#################################
# END CUSTOM SCRIPTS
#################################

# Get the first word from user input.
command=$1
# Get everything after the first word from input.
args=${@:2}

# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then
  if [[ $command == "install" ]]; then
    doitinstall $args
  elif [[ $command == "rebuild" ]]; then
    doitrebuild $args
  elif [[ $command == "welcome" ]]; then
    doitbrand
  else
    echo "$command is not a valid command. Please use \"./doit help\" to see what is available."
  fi
else
# The user did not give any input.
  echo $'\r'
  doitbrand
  doithelp
fi