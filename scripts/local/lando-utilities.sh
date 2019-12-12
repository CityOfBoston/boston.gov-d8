#!/bin/bash

# Define colors
Black='\033[0;30m'
DarkGray='\033[1;30m'
Red='\033[1;31m'
LightRed='\033[0;31m'
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
NC='\033[0m'

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
    eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")
}

function printout () {

  if [ "${1}" == "ERROR" ]; then
    col1=${Red}
    col2=${LightRed}
  elif [ "${1}" == "WARNING" ]; then
    col1=${Yellow}
    col2=${BrownOrange}
  elif [ "${1}" == "INFO" ] || [ "${1}" == "STATUS" ]; then
    col1=${LightBlue}
    col2=${Cyan}
  else
    col1=${LightGreen}
    col2=${Green}
  fi

  if [[ -n ${1} ]]; then
    printf "$col1[${1}] "
  fi
  if [[ -n ${2} ]]; then
      printf "$col2${2}$NC "
  fi
  if [[ -n ${3} ]]; then
    printf "$LightGray- ${3}$NC"
  fi
  printf "\n"
}

function clone_private_repo() {
  printout "INFO" "Clone private repo and merge with main repo."
  git clone git@github.com:CityOfBoston/boston.gov-d8-private.git /app/tmprepo -q --depth 1 &&
    rm -rf /app/tmprepo/.git &&
    find /app/tmprepo/. -iname '*..gitignore' -exec rename 's/\.\.gitignore/\.gitignore/' '{}' \; &&
    rsync -aE /app/tmprepo/ /app/ --exclude=*.md &&
    rm -rf /app/tmprepo &&
    printout "SUCCESS" "Private repo merged."
}