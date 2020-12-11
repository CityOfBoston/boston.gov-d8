#!/bin/bash
# Get the first word from user input.
command=$1

# Run a function based on the command the user supplied.
if [[ -n "$command" ]]; then
    if [[ "${command}" == "database" ]]; then
        mysql -hdatabase -P3306 -udrupal --password=drupal -e";" &> /dev/null && good="MySQL Creds: OK - " ||
          fail="Cannot connect to MySQL Server (possibly credentials?)"
        if [[ -z $good ]]; then
          echo "${fail}"
          exit 1
        fi

        mysql -hdatabase -P3306 -udrupal --password=drupal -e"show databases;" | grep drupal &> /dev/null && good="${good} Drupal DB: Exists - " ||
          fail="Drupal DB doesn't exist or is unavailable"
        if [[ ! -z $fail ]]; then
          echo "${good} ${fail}"
          exit 1
        fi

        echo "${good}"
        exit 0

    elif [[ "${command}" == "node" ]]; then
        if [[ ! -e ${LANDO_MOUNT}/patterns/.ready ]]; then
          echo "Waiting for container to finish building ..."
          exit 0
        fi

        if [[ ! -d ${LANDO_MOUNT}/patterns/public/css ]]; then
            # Nothing is loaded yet, so just report all is good.
            fail="! Patterns is not installed and/or built - "
        else
            good="Patterns installed and built - "
        fi
        if [[ ! -e ${LANDO_MOUNT}/webapps/package.json ]]; then
            # Nothing is loaded yet, so just report all is good.
            fail="${fail}! Web_apps not installed - "
        else
            good="${good}Web_apps installed - "
        fi
        if [[ ! -z $fail ]]; then
          # Go no further if there are errors.
          echo "${good} ${fail}"
          exit 1
        fi

        # If the patterns folder is populated, then try to get the fractal server
        curl --fail -k -m5 http://127.0.0.1 &> /dev/null && good="${good} HTTP_Server (port 80): OK - " ||
          fail="${fail}HTTP_Server:80 not responding - "
        if [[ -z $fail ]]; then
          echo "${good}"
          exit 0
        else
          echo "${fail}"
          exit 1
        fi

    elif [[ "${command}" == "appserver" ]]; then
        # If one or other (probably the wget) of these health checks runs before the Drupal website
        # (which needs a database) it can cause the apache service in the appserver to maxx-out and
        # consume all RAM and CPU available to the container.
        # Use the .dbready flag file to indicate that the Drupal file structure and a database are
        # in place, or else return a (positive) building notification to the docker healthcheck.
        if [[ ! -e ${LANDO_MOUNT}/.dbready ]]; then
          echo "Waiting for container to finish building ..."
          exit 0
        fi

        mysql -hdatabase -P3306 -udrupal --password=drupal -e";" &> /dev/null && good="MySQL Creds: OK - " ||
          fail="Cannot connect to MySQL Server (possibly credentials?)"
        if [[ -z $good ]]; then
          echo "${fail}"
          exit 1
        fi

        mysql -hdatabase -P3306 -udrupal --password=drupal -e"show databases;" | grep drupal &> /dev/null && good="${good} Drupal DB: Exists - " ||
          fail="Drupal DB doesn't exist or is unavailable"
        if [[ ! -z $fail ]]; then
          echo "${good} ${fail}"
          exit 1
        fi

        if [[ -z $fail ]]; then
          curl --fail -k -m5 http://boston.lndo.site &> /dev/null && good="${good} Apache Service: OK - " ||
            fail="Drupal (Apache) Website Down"
        fi
        if [[ ! -z $fail ]]; then
          echo "${good} ${fail}"
          # This frees up any hanging apache processes
          service apache2 stop &> /dev/null && service apache2 start &> /dev/null
          exit 1
        fi

        echo "${good}"
        exit 0
    fi
fi
