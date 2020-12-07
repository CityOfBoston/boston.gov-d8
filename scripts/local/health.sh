#!/bin/bash
# Get the first word from user input.
command=$1

# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then
    if [[ "${command}" == "database" ]]; then
        mysql -h127.0.0.1 -P3306 -udrupal --password=drupal drupal -e"show databases;" &> /dev/null &&
          echo "DB exists and connection is OK" ||
          (echo "MySQL drupal DB not responding" && exit 1)
        exit 0

    elif [[ "${command}" == "node" ]]; then
        if [[ -d /app/patterns/public/css ]]; then
            # If the patterns folder is populated, then try to get the fractal server
            wget http://127.0.0.1 -O .null0.test &> /dev/null && good="${good}HTTP_Server: OK - " ||
              fail="${fail}HTTP_Server:80 not responding - "
            rm -f .null0.test
            if [[ -z $fail ]]; then
              echo "${good}"
              exit 0
            else
              echo "${fail}"
              exit 1
            fi
        else
            # Nothing is loaded yet, so just report all is good.
            echo "! Empty Container (no patterns app installed)"
            exit 0
        fi

    elif [[ "${command}" == "appserver" ]]; then
        mysql -hdatabase -P3306 -udrupal --password=drupal -e"show databases;" &> /dev/nul && good="MySQL Connection: OK - " ||
          fail="No DB Connection - "
        wget http://boston.lndo.site -O .null1.test &> /dev/null && good="${good} Apache Service: OK - " ||
          fail="${fail}Website Down - "
        rm -f .null1.test
        if [[ -z $fail ]]; then
          echo "${good}"
          exit 0
        else
          echo "${fail}"
          exit 1
        fi
    fi
fi
