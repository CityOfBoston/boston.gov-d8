#!/bin/bash
# Get the first word from user input.
command=$1

# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then
    if [[ "${command}" == "database" ]]; then
        mysql -h127.0.0.1 -P3306 -udrupal --password=drupal -e"show databases;"  || exit 1
        exit 0
    elif [[ "${command}" == "patterns" ]]; then
        wget http://127.0.0.1 -O .null0.test || exit 1
        exit 0
    elif [[ "${command}" == "appserver" ]]; then
         mysql -hdatabase -P3306 -udrupal --password=drupal -e"show databases;" && wget http://boston.lndo.site -O .null1.test || exit 1
        exit 0
    fi
fi
