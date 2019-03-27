# BUILD LAUNCH SCRIPTS 

You can build a scripts (like example below) to launch and control build processes.    
DoIT use phing scripting, but still use bash scripts to destroy and re-create your development environment etc.

## Example build script ##
```
cd <path to repo-root folder>
lando destroy -y
cd <path to folder containing repo-root>
sudo rm -rf ./boston.gov-d8/
git clone -b develop git@github.com:CityOfBoston/boston.gov-d8.git boston.gov-d8
cd <path to repo-root folder>
lando start
```
