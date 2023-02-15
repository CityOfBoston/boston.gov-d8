# Release Methodology

## Deploy Pipeline Overview
For this repository, the deploy pipeline follows these steps:
- Merge a working branch to `develop` branch => triggers a deploy to Acquia dev environment
- Merge `develop` branch into `master` branch => triggers a deploy to Acquia stage environment
- Manually deploy from `Acquia Stage` to `Acquia Production`
- Merge `master` brnach into `production` branch => nothing is triggered

## Lead Developer: Tag and release `Production` branch
After the final step of the deployment pipeline, the lead developer must tag and release the production branch so that the 
Project Manager/s can complete their Release Notes.

When the code on `stage` is deployed to Acquia, take note of the tag automatically applied by Acquia (*typically tags/yyyy-mm-dd*)

Once the `master` branch is merged into the `production` branch, then:
1. goto the [release section](https://github.com/CityOfBoston/boston.gov-d8/releases) of the repository,
2. note the last release number, *it will be in the format v9.2023.n - where 9 is current Major Drupal version; 20XX is the year; n is a number which increments with each release*
3. click the "Draft a New Release" button
4. click on "Choose a Tag" and create a new tag with the same name as the Acquia Tag (i.e. _tags/yyyy-mm-dd_)
5. ensure the Target is the `production` branch
6. give the release a title.  Assuming the Major Drupal version has not changed, and the year has not changed, this will just be adding 1 to n from step 2 above (e.g. the current latest release is **v9.2023.12**, then this release's name will be **v9.2023.13**.)
7. in the Description, copy and paste in the template below, then click the `Generate release notes` button to append the commits to be bottom of the textbox. Update the "Jira Tickets` section with all tickets that have been addressed in this release.
8. click "Set as the latest release",
9. click the `Save draft` button.

## Project Manager: Release `Production` branch
The Project Manager will edit the draft release notes, finalize and publish them.
1. goto the [release section](https://github.com/CityOfBoston/digital-terraform/releases) of the repository,
2. edit the latest draft release,
3. update the *[PM to complete]* block with narrative related to the release,
4. click "Set as the latest release",
5. click the `Publish release` button.

A Github action <img src="https://s3-us-west-2.amazonaws.com/slack-files2/bot_icons/2023-02-09/4779927044435_48.png" alt="" style="width: 20px; height: 20px"/> will now fire which will post a message to the slack [#jira-releases channel](https://cityofboston-doit.slack.com/archives/C03UZ01E5N2).

# Release Description Template 
```
## [Copy title of production PR]

### Release Notes
[PM to complete]

### Related Jira tickets
[Add a list of Jira Tickets addressed in this Release, with links to the Jira website]
example: Dig-1839 - [Update residential exemption application in Assessing Online](https://bostondoit.atlassian.net/browse/DIG-1839)

### Acquia tags
[add in the acquia tag]
```
## Project Manager: Release Jira Tickets 
1. In Jira create a release with the following convention RepositoryName/release version (e.g. Boston.gov-D8/v9.2023.2) 
2. The release description should include what was updated and a link to the release notes (e.g. Boston.gov code updates[Release Notes](https://github.com/CityOfBoston/boston.gov-d8/releases/tag/v9.2023.2))
3. Attached release fix version to tickets before releasing the tickets. 
