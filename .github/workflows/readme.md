# Running Github Actions locally

1. Find and install ACT, a third party CLI which runs GitHub Actions in a local docker container.
- @try (on linux only) (from root folder) curl https://raw.githubusercontent.com/nektos/act/master/install.sh > install_act.sh
- @see Repo Source https://github.com/nektos/act
- @see binaries (windows, mac, linux) https://github.com/nektos/act/releases
- @see Userguide https://nektosact.com/usage/index.html
2. Ensure you have a functioning Docker installation and that your user account can launch docker containers from the command line.
3. Create a `.env` file with your GitHub Actions variables in it, and a `.secrets` file with your GitHub Actions secrets in it.
3. run
```
/bin/act  -W .github/workflows/test_workflow.yml --var-file '.github/workflows/.env' --secret-file '.github/workflows/.secrets' --action-offline-mode push
```
- `-W` points to the workflow you require
- `--var-file` points to the file with the variables in it
- `--secret-file` points to the secrets file
- `push` is the event trigger you wish to fire
- and optionally `-action-offline-mode` stops the docker images being pulled each run.
@more run options [here](https://nektosact.com/usage/index.html)
## Knowledgebase
1. **DO NOT COMMIT THE `.env` OR `.secrets` FILE TO THE REPO!**.


2. **If steps crash - Check the runner you are using.**

The first time you run ACT you will be asked to select a **runner**.  More information on the GitHub runner used is found [here](https://nektosact.com/usage/runners.html) - typically **medium** is the best choice, but if your Actions fail locally for no apparent reason, and you really have to test the failing step, try the **large** image (**CARE:** the large image is +/-20GB download and +/-60GB on-disk!) before losing all hope!

[Here](https://github.com/catthehacker/docker_images) is a list of available runners. You can force a different runner by adding this `-P` option to the act command
```yaml
-P <package>=nektos/image_name:tag
```
where `<package>` is the `runs-on` value defined in the workflow being executed.
(e.g. `act -P ubuntu-18.04=nektos/act-environments-ubuntu:18.04`)

Alternatively, you can try to find the missing package you require and then install that into the runner manually in one of the first steps.



