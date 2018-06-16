# boston.gov-d8
1. Ensure you have docker installed on your host computer [**Installation**](https://docs.docker.com/install/).
1. Ensure you have Lando installed on your host computer [**git repo**](https://github.com/lando/lando) and [**Install Instructions**](https://github.com/lando/lando)
1. Clone this repo into a local folder
 `git clone git@github.com:CityOfBoston/boston.gov-d8.git`
1. On host computer, change directory to the repo root and run lando to create and start containers:  
`lando start`
1. From repo root (on host) -view **lando** commands 
`lando`
1. From repo root (on host) -view **phing** tasks 
`lando phing -l`
1. From repo root (on host) -run **drush** commands 
`lando drush <command>`
1. From repo root (on host) -**ssh** into container 
* `lando ssh` to ssh and login as `www-data`
* `lando ssh -user=root` to ssh and login as root.
* `lando ssh <servicename>` servicename = appserver / database / node

