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
