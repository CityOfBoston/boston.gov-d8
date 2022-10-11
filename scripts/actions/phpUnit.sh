#!/bin/bash
#################################################
# Script phpUnit.sh
# Author: David Upton <david.upton@boston.gov>
#
# Runs on a Github actions runner machine.
# Launches PHPUnit tests as required.
#################################################
pwd=$(pwd);
# Include our utility functions.
source "${pwd}/utility.sh"

root=${1}

php -d memory_limit=-1 ${root}/vendor/bin/phpunit
