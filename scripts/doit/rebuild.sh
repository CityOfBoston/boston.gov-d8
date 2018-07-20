#!/bin/bash

if [ $1 == "quick" ]; then
    QUICK=1
elif [ $1 == "full" ]; then
    QUICK=0
else
    echo "Rebuild not specified, execute [q]uick or [f]ast ?"
    read -p "" qf
        case $qf in
            [Qq]* ) QUICK=1; break;;
            [Aa]* ) QUICK=0;;
            * ) echo "Please answer q (quick) or f (fast).";;
        esac
fi

if [ ${QUICK} == 1 ]; then
    echo "FAST Re-install (uses existing repo)"
    cd ~/sources/drupal8/boston.gov-d8/
    cd ~/sources/drupal8/
    sudo rm -rf ./boston.gov-d8/docroot/core
    sudo rm -f ./boston.gov-d8/docroot/sites/default/settings*.php
    sudo rm -rf ./boston.gov-d8/docroot/modules/contrib/*
    sudo rm -rf ./boston.gov-d8/config/sync/*
    sudo rm -f ./boston.gov-d8/setup/*
    sudo rm -f ./boston.gov-d8/composer.lock
    sudo chmod -R 777 ./boston.gov-d8/docroot/sites/default/
    cd ~/sources/drupal8/boston.gov-d8/
    lando rebuild -y
    cd ~/sources/drupal8/boston.gov-d8/
else
    echo "FULL Rebuild (re-clones the repo)"
    cd ~/sources/drupal8/boston.gov-d8/
    lando destroy -y
    cd ~/sources/drupal8/
    sudo rm -rf ./boston.gov-d8
    git clone -b develop git@github.com:CityOfBoston/boston.gov-d8.git boston.gov-d8
    cd ~/sources/drupal8/boston.gov-d8/
    lando start
    cd ~/sources/drupal8/boston.gov-d8/
#    lando restart boston_appserver_1
fi
