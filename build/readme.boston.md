This folder defines the Phing processes.

Phing is required and is installed by `composer` via Lando (called from the ``.lando.yml`` file).  Phing is installed the first time lando starts, and then checked and updated or re-installed each time a `composer update` or  `composer install` is run.

Phing commands can be run from the host machine using `lando phing <phing target>` or from inside the appserver container (use `lando ssh` to launch a terminal inside the container) using `phing <phing target>`.

The main systems with automated processes using Phing are Travis, Terraform and Lando (during `lando start`, `lando restart`, and `lando rebuild`).  These have their a main entry-point in the project **build** (./build/custom/phong/build.xml) and they access targets as appropriate in other projects (in files in ./build/custom/phing/tasks).

Phing commands (or `lando phing` commands) can access any targets, type `lando phing -l` to see the eligible endpoints and a description of each.     
